<?php

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Menu;
use App\Models\Page;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('creates a row for every page and opens them with the current texts', function () {
    Livewire::test(ListPages::class)->assertSee('О ресторане')->assertSee('Страница не найдена (404)');
    expect(Page::count())->toBe(8);

    Livewire::test(EditPage::class, ['record' => Page::firstWhere('key', 'about')->getRouteKey()])
        ->assertFormSet([
            'seo_title' => 'О ресторане | ФабрикантЪ - Ресторан с собственной пивоварней | Симферополь',
            'content.brewery.beers_title' => 'Нефильтрованное, непастеризованное',
        ]);
});

it('changes the about page texts and hides the job ad', function () {
    Repeater::fake();
    Livewire::test(ListPages::class);
    $about = Page::firstWhere('key', 'about');

    Livewire::test(EditPage::class, ['record' => $about->getRouteKey()])
        ->fillForm([
            'seo_title' => 'О нас',
            'content.brewery.beers' => [['name' => 'Стаут', 'note' => 'алк. 6%']],
            'content.kitchen.plaque' => [['text' => 'свой сыр']],
            'content.job.show' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($about->refresh()->content['brewery']['beers'])->toBe([['name' => 'Стаут', 'note' => 'алк. 6%']]);
    $this->get('/about')
        ->assertSee('<title>О нас</title>', false)
        ->assertSee('<dt>Стаут</dt><dd>алк. 6%</dd>', false)
        ->assertSee('<li>свой сыр</li>', false)
        ->assertDontSee('Ищем в команду')
        ->assertDontSee('#rubric-rabota')
        // Остальное — как было
        ->assertSee('Гордость Фабриканта — пиво, сваренное на собственной пивоварне.');
});

it('puts the menu season into the menu page description', function () {
    Menu::factory()->create(['status' => 'published', 'published_at' => now(), 'season' => 'Зима 2027', 'pages_count' => 0]);
    Page::create(['key' => 'menu', 'seo_description' => 'Меню на {сезон}.']);

    $this->get('/menu')->assertSee('<meta name="description" content="Меню на зима 2027.">', false);
});
