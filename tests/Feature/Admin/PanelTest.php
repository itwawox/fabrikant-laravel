<?php

use App\Models\User;
use Filament\Auth\Pages\Login;
use Livewire\Livewire;

it('shows the login page to guests', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('ФабрикантЪ');
});

it('redirects guests from the dashboard to login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('logs a user in by e-mail and password', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    Livewire::test(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'secret-password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);
});

it('opens the dashboard for a signed-in user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertOk();
});
