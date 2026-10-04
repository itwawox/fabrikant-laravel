<?php

use App\Enums\MenuStatus;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Resources\Menus\Pages\MenuSections;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Анна']);
    $this->actingAs($this->user);
});

it('lists menus with status and page counts', function () {
    $menus = Menu::factory()->count(2)->create(['status' => MenuStatus::Ready]);

    $this->get('/admin/menus')->assertOk()->assertSee('Загрузить новое меню');
    Livewire::test(ListMenus::class)->assertCanSeeTableRecords($menus);
});

it('uploads a PDF and gets a ready menu without the terminal', function () {
    if (! Process::run(['gs', '--version'])->successful()) {
        $this->markTestSkipped('Нет Ghostscript');
    }
    Storage::fake('local');
    Storage::fake('public');
    config(['menu.page.width' => 300, 'menu.page.low_width' => 200, 'menu.thumb.width' => 100, 'menu.zoom.width' => 400, 'menu.web_pdf.width' => 300]);

    $pdf = UploadedFile::fake()->createWithContent('menu.pdf', file_get_contents(base_path('tests/fixtures/menu/two-pages.pdf')));

    Livewire::test(CreateMenu::class)
        ->fillForm(['season' => 'Зима 2026', 'upload' => $pdf, 'carry_over' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $menu = Menu::sole();
    expect($menu->status)->toBe(MenuStatus::Ready)
        ->and($menu->created_by)->toBe($this->user->id)
        ->and($menu->pages_count)->toBe(2);

    // Подписи страниц правятся в карточке, затем меню публикуется
    Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->assertSee('Готово к публикации')
        ->fillForm(['season' => 'Зима 2026', 'pages' => [
            'record-'.$menu->pages[0]->id => ['title' => 'Супы'],
            'record-'.$menu->pages[1]->id => ['title' => 'Гриль'],
        ]])
        ->call('save')
        ->assertHasNoFormErrors()
        ->callAction('publish');

    expect($menu->refresh()->status)->toBe(MenuStatus::Published)
        ->and($menu->pages->pluck('title')->all())->toBe(['Супы', 'Гриль']);
    $this->get('/menu')->assertOk()->assertSee($menu->storage_dir.'/1-1600.jpg', false);
});

it('rejects files that are not PDF', function () {
    Storage::fake('local');

    Livewire::test(CreateMenu::class)
        ->fillForm(['season' => 'Зима 2026', 'upload' => UploadedFile::fake()->image('menu.jpg')])
        ->call('create')
        ->assertHasFormErrors(['upload']);

    expect(Menu::count())->toBe(0);
});

it('shows the error and a retry button for a failed menu', function () {
    $menu = Menu::factory()->create(['status' => MenuStatus::Draft, 'error' => 'PDF защищён паролем.']);

    Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->assertSee('PDF защищён паролем.')
        ->assertActionVisible('retry')
        ->assertActionHidden('publish');
});

it('rolls back from the list', function () {
    $autumn = Menu::factory()->create(['status' => MenuStatus::Archived, 'published_at' => now()->subMonth()]);
    $winter = Menu::factory()->create(['status' => MenuStatus::Published, 'published_at' => now()]);

    Livewire::test(ListMenus::class)->callAction('rollback');

    expect($autumn->refresh()->status)->toBe(MenuStatus::Published)
        ->and($winter->refresh()->status)->toBe(MenuStatus::Archived);
});

it('schedules publication from the card', function () {
    $menu = Menu::factory()->create(['status' => MenuStatus::Ready]);

    Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
        ->callAction('schedule', ['at' => now()->addDay()->setTime(9, 0)->toDateTimeString()])
        ->assertNotified();

    expect($menu->refresh()->scheduled_at->format('H:i'))->toBe('09:00');
});

it('opens the section editor with pages and sections and saves from it', function () {
    $menu = Menu::factory()->create(['status' => MenuStatus::Ready, 'pages_count' => 2, 'storage_dir' => 'menus/7-abc']);
    $menu->pages()->createMany([['number' => 1, 'title' => 'Супы', 'width' => 1600, 'height' => 2263], ['number' => 2, 'title' => 'Гриль']]);
    $menu->sections()->create(['page_number' => 1, 'title' => 'Супы', 'slug' => 'supy', 'position' => 0])
        ->boxes()->create(['x' => 0.1, 'y' => 0.1, 'w' => 0.3, 'h' => 0.3]);

    $this->get(MenuSections::getUrl(['record' => $menu]))
        ->assertOk()
        ->assertSee('Сохранить разделы')
        ->assertSee('7-abc', false);

    $id = $menu->sections()->value('id');
    $page = Livewire::test(MenuSections::class, ['record' => $menu->getRouteKey()]);
    $out = $page->instance()->save([['id' => $id, 'page' => 1, 'title' => 'Бульоны', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]]]);
    expect($out)->toHaveKey('changes');

    $out = $page->instance()->save([['id' => $id, 'page' => 1, 'title' => 'Бульоны', 'boxes' => [[0.1, 0.1, 0.3, 0.3]]]], 'keep');
    expect($out['saved'])->toBe(1)
        ->and($out['sections'][0])->toMatchArray(['id' => $id, 'title' => 'Бульоны', 'slug' => 'supy', 'slug_locked' => true]);
});

it('copies the markup of the previous menu into the editor', function () {
    $old = Menu::factory()->create(['status' => MenuStatus::Published, 'published_at' => now(), 'pages_count' => 2]);
    $old->sections()->create(['page_number' => 2, 'title' => 'Гриль', 'slug' => 'gril', 'position' => 0])
        ->boxes()->create(['x' => 0.1, 'y' => 0.1, 'w' => 0.3, 'h' => 0.3]);
    $menu = Menu::factory()->create(['status' => MenuStatus::Ready, 'pages_count' => 2]);
    $menu->pages()->createMany([['number' => 1, 'title' => 'Мои подписи'], ['number' => 2]]);

    Livewire::test(MenuSections::class, ['record' => $menu->getRouteKey()])->callAction('copyPrevious');

    expect($menu->sections()->sole()->slug)->toBe('gril')
        ->and($menu->pages()->where('number', 1)->value('title'))->toBe('Мои подписи');
});

it('does not open the section editor for a menu that is still processing', function () {
    $menu = Menu::factory()->create(['status' => MenuStatus::Processing]);

    $this->get(MenuSections::getUrl(['record' => $menu]))->assertNotFound();
});
