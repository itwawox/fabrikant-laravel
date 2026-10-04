<?php

use App\Enums\UserRole;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('records who changed what and shows it to the owner only', function () {
    $owner = User::factory()->create(['name' => 'Ирина']);
    $this->actingAs($owner);

    $promo = Promo::factory()->create(['title' => 'Обед']);
    $promo->update(['title' => 'Бизнес-ланч']);

    $log = Activity::where('subject_type', Promo::class)->where('event', 'updated')->sole();
    expect($log->causer->is($owner))->toBeTrue();

    Livewire::test(ListActivities::class)
        ->assertCanSeeTableRecords([$log])
        ->assertSee('Название: Обед → Бизнес-ланч')
        ->assertSee('Ирина');

    $this->actingAs(User::factory()->create(['role' => UserRole::Manager]))->get('/admin/activities')->assertForbidden();
});

it('does not log technical fields like menu progress or passwords', function () {
    $menu = Menu::factory()->create();
    $menu->update(['progress_done' => 3, 'progress_step' => 'pages']);
    $user = User::factory()->create();
    $user->update(['password' => 'another-password']);

    expect(Activity::where('event', 'updated')->count())->toBe(0);
});
