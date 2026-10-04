<?php

use App\Filament\Pages\PromoSimulator;
use App\Filament\Resources\Holidays\Pages\ManageHolidays;
use App\Filament\Resources\PromoBlackouts\Pages\ManagePromoBlackouts;
use App\Filament\Resources\Promos\Pages\CreatePromo;
use App\Filament\Resources\Promos\Pages\EditPromo;
use App\Filament\Resources\Promos\Pages\ListPromos;
use App\Models\Holiday;
use App\Models\Promo;
use App\Models\PromoBlackout;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('public');
    Storage::fake('local');
});

it('creates a scheduled promo with a processed photo and shows it in the bar', function () {
    // Предсказуемые ключи строк повторителя — иначе fillForm не сопоставит их с полями
    Repeater::fake();

    Livewire::test(CreatePromo::class)
        ->fillForm([
            'title' => 'Счастливые часы',
            'discount' => '−25%',
            'short' => '−25% на меню кухни',
            'rows' => [['label' => 'Когда', 'value' => 'с пн по пт']],
            'terms' => [['text' => 'Не суммируется с другими акциями.']],
            'photo_upload' => UploadedFile::fake()->image('beer.jpg', 1600, 900),
            'scheduled' => true,
            'days' => [5, 1, 2, 3, 4],
            'time_from' => '12:00',
            'time_to' => '15:00',
            'not_holidays' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $promo = Promo::sole();
    expect($promo->slug)->toBe('schastlivye-chasy')
        ->and($promo->days)->toBe([1, 2, 3, 4, 5])
        ->and([$promo->time_from, $promo->time_to])->toBe(['12:00', '15:00'])
        ->and($promo->rows)->toBe([['label' => 'Когда', 'value' => 'с пн по пт']])
        ->and($promo->terms)->toBe(['Не суммируется с другими акциями.'])
        ->and(getimagesize(Storage::disk('public')->path($promo->photo_path)))->toMatchArray([0 => 1000, 1 => 750])
        ->and(getimagesize(Storage::disk('public')->path($promo->photo_webp_path)))->toMatchArray([0 => 800, 1 => 600]);

    // Понедельник 5 октября 2026, 13:00 — акция идёт
    $this->travelTo(now('Europe/Simferopol')->setDate(2026, 10, 5)->setTime(13, 0));
    $this->get('/promos')->assertSee('Сейчас, до 15:00');
});

it('clears the schedule when the promo is unbound from time and keeps all-day promos without hours', function () {
    $promo = Promo::factory()->create(['days' => [1], 'time_from' => '12:00', 'time_to' => '15:00', 'not_holidays' => true]);

    Livewire::test(EditPromo::class, ['record' => $promo->getRouteKey()])
        ->assertFormSet(['scheduled' => true, 'all_day' => false])
        ->fillForm(['all_day' => true])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($promo->refresh()->only(['days', 'time_from', 'time_to']))->toBe(['days' => [1], 'time_from' => null, 'time_to' => null]);

    Livewire::test(EditPromo::class, ['record' => $promo->getRouteKey()])
        ->fillForm(['scheduled' => false])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($promo->refresh()->days)->toBeNull()->and($promo->not_holidays)->toBeFalse();
});

it('replaces the photo and deletes the old files', function () {
    $promo = Promo::factory()->create(['photo_path' => 'promos/old.jpg', 'photo_webp_path' => 'promos/old.webp']);
    Storage::disk('public')->put('promos/old.jpg', 'x');
    Storage::disk('public')->put('promos/old.webp', 'x');

    Livewire::test(EditPromo::class, ['record' => $promo->getRouteKey()])
        ->fillForm(['photo_upload' => UploadedFile::fake()->image('new.png', 1200, 1200)])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($promo->refresh()->photo_path)->not->toBe('promos/old.jpg')
        ->and(Storage::disk('public')->exists('promos/old.jpg'))->toBeFalse()
        ->and(Storage::disk('public')->exists($promo->photo_path))->toBeTrue();
});

it('rejects an end time before the start time', function () {
    Livewire::test(CreatePromo::class)
        ->fillForm(['title' => 'Обед', 'scheduled' => true, 'days' => [1], 'time_from' => '15:00', 'time_to' => '12:00'])
        ->call('create')
        ->assertHasFormErrors(['time_to']);
});

it('lists promos with their schedule in one line', function () {
    Promo::factory()->create(['title' => 'Счастливые часы', 'days' => [1, 2, 3, 4, 5], 'time_from' => '12:00', 'time_to' => '15:00', 'not_holidays' => true]);

    Livewire::test(ListPromos::class)->assertSee('Пн–Пт 12:00–15:00, кроме праздников');
});

it('adds official holidays once and manages blackout days', function () {
    Holiday::create(['month_day' => '05-09', 'title' => 'Мой заголовок']);

    Livewire::test(ManageHolidays::class)->callAction('official');
    expect(Holiday::count())->toBe(count(Holiday::OFFICIAL))
        ->and(Holiday::where('month_day', '05-09')->value('title'))->toBe('Мой заголовок');

    Livewire::test(ManagePromoBlackouts::class)
        ->callAction('create', ['date' => '2026-11-14', 'reason' => 'Концерт'])
        ->assertHasNoActionErrors();
    expect(PromoBlackout::sole()->reason)->toBe('Концерт');
});

it('simulates the bar for any date and time', function () {
    Promo::factory()->create(['title' => 'Счастливые часы', 'slug' => 'happy', 'days' => [1, 2, 3, 4, 5], 'time_from' => '12:00', 'time_to' => '15:00', 'not_holidays' => true]);
    PromoBlackout::create(['date' => '2026-10-06', 'reason' => 'Концерт']);

    Livewire::test(PromoSimulator::class)
        ->set('at', '2026-10-05T14:00')->assertSee('Сейчас, до 15:00')
        ->set('at', '2026-10-06T14:00')->assertDontSee('Сейчас, до 15:00')->assertSee('Завтра с 12:00');

    $this->get('/admin')->assertOk()->assertSee('Акции: что гость видит сейчас');
});

it('hides a promo from the promos page after its period ends', function () {
    Promo::factory()->create(['title' => 'Октоберфест', 'slug' => 'oktoberfest', 'days' => [1, 2, 3, 4, 5, 6, 7], 'valid_to' => '2026-10-08']);

    $this->travelTo(now('Europe/Simferopol')->setDate(2026, 10, 8)->setTime(20, 0));
    $this->get('/promos')->assertSee('id="oktoberfest"', false);

    $this->travelTo(now('Europe/Simferopol')->setDate(2026, 10, 9)->setTime(10, 0));
    $this->artisan('responsecache:clear');
    $this->get('/promos')->assertDontSee('id="oktoberfest"', false)->assertDontSee('Октоберфест');
});
