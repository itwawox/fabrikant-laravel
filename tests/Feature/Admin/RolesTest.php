<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

$pages = [
    'menu' => '/admin/menus', 'promos' => '/admin/promos', 'gallery' => '/admin/gallery-photos',
    'bookings' => '/admin/bookings', 'content' => '/admin/pages', 'settings' => '/admin/settings', 'users' => '/admin/users',
];

it('shows each role only its sections', function (UserRole $role, array $allowed) use ($pages) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    foreach ($pages as $area => $url) {
        in_array($area, $allowed, true)
            ? $this->get($url)->assertOk()
            : $this->get($url)->assertForbidden();
    }
    $this->get('/admin')->assertOk();
})->with([
    'владелец' => [UserRole::Owner, ['menu', 'promos', 'gallery', 'bookings', 'content', 'settings', 'users']],
    'менеджер' => [UserRole::Manager, ['menu', 'promos', 'gallery', 'bookings']],
    'SMM' => [UserRole::Smm, ['gallery', 'content']],
]);

it('lets the owner add a manager and send a password link', function () {
    Notification::fake();
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageUsers::class)
        ->callAction('create', ['name' => 'Олег', 'email' => 'oleg@example.com', 'role' => 'manager', 'password' => 'long-enough-1'])
        ->assertHasNoActionErrors();
    $oleg = User::firstWhere('email', 'oleg@example.com');
    expect($oleg->role)->toBe(UserRole::Manager);

    Livewire::test(ManageUsers::class)->callTableAction('reset', $oleg)->assertNotified('Письмо отправлено');
    Notification::assertSentTo($oleg, ResetPassword::class);
});

it('never leaves the panel without an owner', function () {
    $owner = User::factory()->create();
    $this->actingAs($owner);

    Livewire::test(ManageUsers::class)
        ->assertTableActionHidden('delete', $owner)
        ->callTableAction('edit', $owner, ['role' => 'smm']);

    expect($owner->refresh()->role)->toBe(UserRole::Owner);
});
