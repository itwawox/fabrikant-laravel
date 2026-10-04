<?php

use App\Enums\MenuStatus;
use App\Models\Menu;
use App\Services\Menu\MenuPdfException;
use App\Services\Menu\MenuPublisher;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(fn () => $this->publisher = app(MenuPublisher::class));

it('publishes a ready menu and archives the current one', function () {
    $old = Menu::factory()->create(['status' => MenuStatus::Published, 'published_at' => now()->subMonth()]);
    $new = Menu::factory()->create(['status' => MenuStatus::Ready, 'scheduled_at' => now()->addDay()]);

    $this->publisher->publish($new);

    expect($old->refresh()->status)->toBe(MenuStatus::Archived)
        ->and($new->refresh()->status)->toBe(MenuStatus::Published)
        ->and($new->scheduled_at)->toBeNull()
        ->and(Menu::published()->count())->toBe(1);
});

it('refuses to publish a menu that is not processed', function (MenuStatus $status) {
    $this->publisher->publish(Menu::factory()->create(['status' => $status]));
})->with([MenuStatus::Draft, MenuStatus::Processing])->throws(MenuPdfException::class);

it('rolls back to the previously published menu', function () {
    $autumn = Menu::factory()->create(['status' => MenuStatus::Ready]);
    $winter = Menu::factory()->create(['status' => MenuStatus::Ready]);
    $this->publisher->publish($autumn);
    $this->travel(1)->minutes();
    $this->publisher->publish($winter);

    expect($this->publisher->rollback()->is($autumn))->toBeTrue()
        ->and($autumn->refresh()->status)->toBe(MenuStatus::Published)
        ->and($winter->refresh()->status)->toBe(MenuStatus::Archived);
});

it('cannot roll back without a previous menu', function () {
    $this->publisher->rollback();
})->throws(MenuPdfException::class, 'Нет прошлого меню');

it('publishes a scheduled menu when its time comes', function () {
    $menu = Menu::factory()->create(['status' => MenuStatus::Ready]);
    $this->publisher->schedule($menu, now()->addHour());

    $this->artisan('menu:publish-scheduled')->assertSuccessful();
    expect($menu->refresh()->status)->toBe(MenuStatus::Ready);

    $this->travel(61)->minutes();
    $this->artisan('menu:publish-scheduled')->assertSuccessful();
    expect($menu->refresh()->status)->toBe(MenuStatus::Published);
});

it('deletes an archived menu with its files but never the published one', function () {
    Storage::fake('local');
    Storage::fake('public');
    $archived = Menu::factory()->create(['status' => MenuStatus::Archived, 'storage_dir' => 'menus/9-old']);
    Storage::disk('public')->put('menus/9-old/1-1600.webp', 'x');
    Storage::disk('local')->put('menus/9-old/source.pdf', 'x');

    $this->publisher->delete($archived);

    expect(Menu::find($archived->id))->toBeNull()
        ->and(Storage::disk('public')->exists('menus/9-old'))->toBeFalse()
        ->and(Storage::disk('local')->exists('menus/9-old'))->toBeFalse();

    $this->publisher->delete(Menu::factory()->create(['status' => MenuStatus::Published]));
})->throws(MenuPdfException::class);

it('shows an unpublished menu only by a signed preview link', function () {
    $menu = Menu::factory()->create(['status' => MenuStatus::Ready, 'storage_dir' => 'menus/5-new', 'pages_count' => 1]);
    $menu->pages()->create(['number' => 1, 'title' => 'Супы', 'width' => 1600, 'height' => 2263]);

    $this->get('/menu/preview/'.$menu->id)->assertForbidden();

    $this->get(URL::temporarySignedRoute('menu.preview', now()->addWeek(), ['menu' => $menu]))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('/storage/menus/5-new/1-1600.jpg', false);
});
