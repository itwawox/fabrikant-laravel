<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Models\User;
use App\Settings\SiteSettings;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('has the legacy contacts out of the box', function () {
    $this->get('/contacts')
        ->assertSee('Симферополь, улица Киевская, 54')
        ->assertSee('href="tel:+79788072001"', false)
        ->assertSee('ежедневно 11:00–23:00')
        ->assertSee('yandex.ru/map-widget/v1/org/fabrikant/1324964934/', false);
});

it('changes contacts on every page from the settings page', function () {
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['phone' => '+7 978 000 11 22', 'street' => 'Пушкина', 'house' => '1', 'opens_at' => '10:00', 'pr_email' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(SiteSettings::class)->opens_at)->toBe('10:00');
    $this->get('/contacts')
        ->assertSee('Симферополь, улица Пушкина, 1')
        ->assertSee('href="tel:+79780001122"', false)
        ->assertSee('ежедневно 10:00–23:00')
        ->assertDontSee('PR-служба');
    $this->get('/about')->assertSee('Симферополь, Пушкина, 1 &bull; Каждый день с 10:00 до 23:00', false);
    $this->get('/promos')->assertSee('Симферополь, ул. Пушкина, 1.');
});

it('turns the Metrika counter off and on with another number', function () {
    $this->get('/')->assertSee('yaCounter26918373');

    Livewire::test(ManageSiteSettings::class)->fillForm(['metrika_enabled' => false])->call('save')->assertHasNoFormErrors();
    $this->get('/')->assertDontSee('mc.yandex.ru/metrika');

    Livewire::test(ManageSiteSettings::class)->fillForm(['metrika_enabled' => true, 'metrika_id' => '123'])->call('save')->assertHasNoFormErrors();
    $this->get('/')->assertSee('yaCounter123');
});
