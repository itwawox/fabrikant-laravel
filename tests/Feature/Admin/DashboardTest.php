<?php

use App\Enums\MenuStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('shows the menu age, bookings and site status to the owner', function () {
    $this->actingAs(User::factory()->create());
    Menu::factory()->create(['status' => MenuStatus::Published, 'published_at' => now()->subDays(70), 'season' => 'Лето 2026']);
    Booking::factory()->create(['name' => 'Пётр']);

    $this->get('/admin')->assertOk()
        ->assertSee('Лето 2026')
        ->assertSee('70 дней назад')
        ->assertSee('Пора проверить цены')
        ->assertSee('Последний бэкап')
        ->assertSee('Акции: что гость видит сейчас');
});

it('hides owner widgets from SMM', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::Smm]));

    $this->get('/admin')->assertOk()->assertDontSee('Последний бэкап')->assertDontSee('Меню у гостей');
});

it('reminds by mail when the menu is older than 60 days', function () {
    Mail::fake();
    Menu::factory()->create(['status' => MenuStatus::Published, 'published_at' => now()->subDays(61)]);

    $this->artisan('menu:remind-stale')->expectsOutput('Напоминание отправлено');
});

it('backs up the database and files', function () {
    Storage::fake('backups');
    $this->artisan('backup:run --only-db')->assertSuccessful();

    expect(Storage::disk('backups')->allFiles())->not->toBeEmpty();
});
