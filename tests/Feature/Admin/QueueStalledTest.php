<?php

use App\Enums\MenuStatus;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Models\Menu;
use App\Models\User;
use App\Support\QueueHealth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

function queueJob(int $secondsAgo): void
{
    DB::table('jobs')->insert([
        'queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'reserved_at' => null,
        'available_at' => now()->subSeconds($secondsAgo)->getTimestamp(), 'created_at' => now()->subSeconds($secondsAgo)->getTimestamp(),
    ]);
}

it('says plainly that the queue is not running when a job waits for minutes', function () {
    $menu = Menu::factory()->create(['status' => MenuStatus::Processing, 'progress_step' => 'inspect']);
    queueJob(30);
    expect(QueueHealth::stalled())->toBeFalse();
    Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])->assertDontSee('Обработка не началась');

    queueJob(300);
    expect(QueueHealth::stalled())->toBeTrue();
    Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->assertSee('Обработка не началась')
        ->assertSee(QueueHealth::advice());
    // Баннер — на любой странице админки
    $this->get('/admin/promos')->assertSee('Обработка меню и фото стоит');
    $this->get('/admin')->assertSee('не запущена');
});

it('offers a retry when processing stopped half way', function () {
    $menu = Menu::factory()->create(['status' => MenuStatus::Processing, 'progress_step' => 'pages', 'progress_done' => 3, 'progress_total' => 8]);
    Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])->assertActionHidden('retry');

    $this->travel(6)->minutes();
    Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->assertSee('Обработка остановилась')
        ->assertActionVisible('retry');
});
