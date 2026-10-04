<?php

use App\Enums\MenuStatus;
use App\Models\Menu;
use App\Services\Menu\GhostscriptRenderer;
use App\Services\Menu\MenuFiles;
use App\Services\Menu\MenuPipeline;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

// Конвейер на настоящем Ghostscript и маленьком PDF из двух страниц (A5). Без gs тесты пропускаются.
beforeEach(function () {
    if (! Process::run(['gs', '--version'])->successful()) {
        $this->markTestSkipped('Нет Ghostscript');
    }
    Storage::fake('local');
    Storage::fake('public');
    config(['menu.renderer' => 'gs']);
});

// Размеры картинок проверяет первый тест; остальным хватит маленьких — так в пять раз быстрее
function smallImages(): void
{
    config(['menu.page.width' => 300, 'menu.page.low_width' => 200, 'menu.thumb.width' => 100, 'menu.zoom.width' => 400, 'menu.web_pdf.width' => 300]);
}

function uploadFixture(string $name = 'two-pages.pdf'): string
{
    Storage::disk('local')->put('livewire-tmp/upload.pdf', file_get_contents(base_path('tests/fixtures/menu/'.$name)));

    return 'livewire-tmp/upload.pdf';
}

it('turns a PDF into a ready menu with all page files and a light PDF', function () {
    $menu = app(MenuPipeline::class)->create('Зима 2026', uploadFixture());
    $menu->refresh();

    expect($menu->status)->toBe(MenuStatus::Ready)
        ->and($menu->error)->toBeNull()
        ->and($menu->pages_count)->toBe(2)
        ->and($menu->sheet_w_pt)->toBe(420.94)
        ->and($menu->web_pdf_path)->toBe($menu->storage_dir.'/web.pdf')
        ->and($menu->pages->pluck('title')->all())->toBe(['Страница 1', 'Страница 2']);

    $files = new MenuFiles($menu);
    foreach ([1, 2] as $n) {
        expect($files->pageReady($n))->toBeTrue();
        expect(array_slice(getimagesize($files->page($n, '1600.jpg')), 0, 2))->toBe([1600, 2263]);
        expect(getimagesize($files->page($n, '1000.webp'))[0])->toBe(1000);
        expect(getimagesize($files->page($n, '200.webp'))[0])->toBe(200);
        expect(getimagesize($files->page($n, '3200.webp'))[0])->toBe(3200);
    }
    expect($menu->pages->first()->width)->toBe(1600)->and($menu->pages->first()->height)->toBe(2263);

    // Лёгкий PDF читается и имеет те же 2 страницы того же размера листа
    $info = app(GhostscriptRenderer::class)->info(Storage::disk('public')->path($menu->web_pdf_path));
    expect($info->pages)->toBe(2)->and(round($info->widthPt))->toBe(421.0);

    // Исходник — только на закрытом диске, временные файлы убраны
    expect(Storage::disk('local')->exists($menu->storage_dir.'/source.pdf'))->toBeTrue()
        ->and(Storage::disk('local')->exists($menu->storage_dir.'/tmp'))->toBeFalse()
        ->and(Storage::disk('public')->exists($menu->storage_dir.'/source.pdf'))->toBeFalse();
});

it('carries page titles and sections over from the published menu with the same page count', function () {
    smallImages();
    $old = Menu::factory()->create(['status' => MenuStatus::Published, 'published_at' => now(), 'pages_count' => 2]);
    $old->pages()->createMany([['number' => 1, 'title' => 'Супы'], ['number' => 2, 'title' => 'Гриль']]);
    $section = $old->sections()->create(['page_number' => 1, 'title' => 'Супы', 'slug' => 'supy', 'slug_locked' => true, 'position' => 0]);
    $section->boxes()->create(['x' => 0.1, 'y' => 0.2, 'w' => 0.3, 'h' => 0.4, 'position' => 0]);

    $menu = app(MenuPipeline::class)->create('Зима 2026', uploadFixture())->refresh();

    expect($menu->pages->pluck('title')->all())->toBe(['Супы', 'Гриль'])
        ->and($menu->sections->sole()->only(['slug', 'slug_locked', 'title']))->toBe(['slug' => 'supy', 'slug_locked' => true, 'title' => 'Супы'])
        ->and($menu->sections->sole()->boxes->sole()->toBox())->toBe([0.1, 0.2, 0.3, 0.4]);
});

it('does not carry markup over when the page count differs or it is switched off', function () {
    smallImages();
    $old = Menu::factory()->create(['status' => MenuStatus::Published, 'published_at' => now(), 'pages_count' => 8]);
    $old->sections()->create(['page_number' => 1, 'title' => 'Супы', 'slug' => 'supy', 'position' => 0]);

    $menu = app(MenuPipeline::class)->create('Зима 2026', uploadFixture())->refresh();
    expect($menu->sections)->toBeEmpty();

    $old->update(['pages_count' => 2]);
    $off = app(MenuPipeline::class)->create('Зима 2026', uploadFixture(), carryOver: false)->refresh();
    expect($off->sections)->toBeEmpty();
});

it('shows a human error for a broken PDF and finishes on retry once the file is fixed', function () {
    smallImages();
    Storage::disk('local')->put('livewire-tmp/upload.pdf', 'это не PDF');

    $menu = rescue(fn () => app(MenuPipeline::class)->create('Зима 2026', 'livewire-tmp/upload.pdf'), report: false);
    $menu = Menu::sole();

    expect($menu->status)->toBe(MenuStatus::Draft)
        ->and($menu->error)->toContain('битый');

    Storage::disk('local')->put($menu->storage_dir.'/source.pdf', file_get_contents(base_path('tests/fixtures/menu/two-pages.pdf')));
    app(MenuPipeline::class)->start($menu);

    expect($menu->refresh()->status)->toBe(MenuStatus::Ready)->and($menu->error)->toBeNull();
});

it('keeps already rendered pages when retrying after a failure', function () {
    smallImages();
    $menu = app(MenuPipeline::class)->create('Зима 2026', uploadFixture())->refresh();
    $files = new MenuFiles($menu);

    // Как после падения на странице 2: страница 1 готова (и её JPEG для лёгкого PDF на месте), страницы 2 нет
    copy($files->page(1, '1600.jpg'), $files->webPdfPage(1));
    touch($files->page(1, '1600.webp'), 1_000_000);
    unlink($files->page(2, '1600.webp'));

    app(MenuPipeline::class)->start($menu);
    clearstatcache();

    expect($menu->refresh()->status)->toBe(MenuStatus::Ready)
        ->and(filemtime($files->page(1, '1600.webp')))->toBe(1_000_000)
        ->and($files->pageReady(2))->toBeTrue();
});
