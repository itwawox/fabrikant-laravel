<?php

use App\Enums\MenuStatus;
use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use App\Models\Holiday;
use App\Models\Menu;
use App\Models\MenuSection;
use App\Models\MenuSectionBox;
use App\Models\Promo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

const LEGACY_MENU = 'menu_restaurant_fabrikant2026_osen';

// Папка «старого сайта» во временном каталоге: данные — копия настоящих (tests/fixtures/legacy),
// файлы — заглушки, чтобы тест не зависел от 50 МБ картинок и PDF.
function makeLegacySite(bool $withFiles = true): string
{
    $root = sys_get_temp_dir().'/legacy-'.uniqid();
    File::copyDirectory(__DIR__.'/../fixtures/legacy/data', $root.'/data');
    if (! $withFiles) {
        return $root;
    }

    $put = function (string $path, string $contents = 'x') use ($root) {
        File::ensureDirectoryExists(dirname($root.'/'.$path));
        File::put($root.'/'.$path, $contents);
    };

    // Настоящий JPEG нужен, чтобы импорт прочитал размер страницы
    $image = imagecreatetruecolor(160, 226);
    ob_start();
    imagejpeg($image);
    $jpeg = ob_get_clean();

    foreach (range(1, 8) as $n) {
        $put('assets/img/menu/'.LEGACY_MENU."-{$n}.jpg", $jpeg);
        foreach (['.webp', '-1000.webp', '-200.webp', '-3200.webp'] as $suffix) {
            $put('assets/img/menu/'.LEGACY_MENU."-{$n}{$suffix}");
        }
    }
    $put('uploads/'.LEGACY_MENU.'.pdf');
    $put('uploads/'.LEGACY_MENU.'-web.pdf');

    $promos = require $root.'/data/promos.php';
    foreach ($promos['promos'] as $promo) {
        $put("assets/img/akcii/{$promo['photo']}.jpg");
        $put("assets/img/akcii/{$promo['photo']}.webp");
    }

    $gallery = require $root.'/data/gallery.php';
    foreach ($gallery['photos'] as $photo) {
        $put($photo['src']);
    }
    $build = require $root.'/data/gallery.build.php';
    foreach ($build as $variants) {
        foreach (['avif', 'webp'] as $format) {
            foreach ($variants[$format] as $url) {
                $put(ltrim($url, '/'));
            }
        }
        $put(ltrim($variants['jpg'], '/'));
    }

    return $root;
}

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/legacy-*') as $dir) {
        File::deleteDirectory($dir);
    }
});

it('imports the menu with pages, sections and files', function () {
    $this->artisan('legacy:import', ['path' => makeLegacySite()])->assertSuccessful();

    $menu = Menu::with('pages', 'sections.boxes')->sole();
    $dir = $menu->storage_dir;

    expect($menu->name)->toBe(LEGACY_MENU)
        ->and($menu->season)->toBe('Осень 2026')
        ->and($menu->status)->toBe(MenuStatus::Published)
        ->and($menu->published_at)->not->toBeNull()
        ->and($menu->pages_count)->toBe(8)
        ->and($menu->pages->pluck('title')->all())->toBe([
            'Пиво и закуски', 'Закуски, салаты, супы', 'Горячее и гриль', 'Десерты, чай, кофе',
            'Лимонады и соки', 'Коктейли, игристое', 'Крепкие напитки', 'Вино',
        ])
        ->and($menu->pages->first()->only('width', 'height'))->toBe(['width' => 160, 'height' => 226])
        ->and($dir)->toStartWith("menus/{$menu->id}-");

    foreach (['1600.jpg', '1600.webp', '1000.webp', '200.webp', '3200.webp'] as $suffix) {
        Storage::disk('public')->assertExists("{$dir}/8-{$suffix}");
    }
    // Исходник типографии — только на закрытом диске
    Storage::disk('local')->assertExists($menu->source_pdf_path);
    Storage::disk('public')->assertMissing("{$dir}/source.pdf");
    Storage::disk('public')->assertExists($menu->web_pdf_path);
});

it('keeps section addresses and order exactly as on the old site', function () {
    $this->artisan('legacy:import', ['path' => makeLegacySite(false)])->assertSuccessful();

    $expected = json_decode(file_get_contents(__DIR__.'/../fixtures/legacy/expected-sections.json'), true)['sections'];
    $sections = Menu::sole()->sections()->with('boxes')->get();

    expect($sections)->toHaveCount(35)
        ->and(MenuSectionBox::count())->toBe(36)
        ->and($sections->map(fn (MenuSection $s) => [
            'id' => $s->slug,
            'page' => $s->page_number,
            'title' => $s->title,
            'boxes' => $s->boxes->map->toBox()->all(),
        ])->all())->toEqualWithDelta($expected, 1e-9);
});

it('imports promos with schedules and holidays', function () {
    $this->artisan('legacy:import', ['path' => makeLegacySite()])->assertSuccessful();

    $promos = Promo::orderBy('position')->get();
    $happy = $promos->firstWhere('slug', 'happyhours');

    expect($promos->pluck('slug')->all())->toBe(['mujskie-dni', 'happyhours', 'imeninnik'])
        ->and($happy->days)->toBe([1, 2, 3, 4, 5])
        ->and([$happy->time_from, $happy->time_to])->toBe(['12:00', '15:00'])
        ->and($happy->not_holidays)->toBeTrue()
        ->and($happy->rows)->toBe([
            ['label' => 'Когда', 'value' => 'с пн по пт'],
            ['label' => 'Часы', 'value' => '12:00 - 15:00'],
        ])
        ->and($promos->firstWhere('slug', 'imeninnik')->hasSchedule())->toBeFalse()
        ->and(Holiday::count())->toBe(14)
        ->and(Holiday::where('month_day', '05-09')->value('title'))->toBe('День Победы');

    Storage::disk('public')->assertExists($happy->photo_path);
});

it('imports gallery rubrics, photos and their copies', function () {
    $this->artisan('legacy:import', ['path' => makeLegacySite()])->assertSuccessful();

    $fasad = GalleryPhoto::where('slug', 'fasad')->sole();

    expect(GalleryRubric::orderBy('position')->pluck('key')->all())->toBe(['dom', 'dvor', 'pivovarnya'])
        ->and(GalleryPhoto::count())->toBe(10)
        ->and($fasad->rubric->key)->toBe('dom')
        ->and([$fasad->width, $fasad->height])->toBe([1024, 682])
        ->and($fasad->variants['jpg'])->toBe('gallery/fasad-1024.7bf860f7.jpg')
        ->and($fasad->variants['avif'])->toHaveCount(3);

    Storage::disk('public')->assertExists($fasad->variants['webp']['480']);
    // Исходник — на закрытом диске: в нём EXIF с координатами
    Storage::disk('local')->assertExists($fasad->source_path);
    Storage::disk('public')->assertMissing($fasad->source_path);
});

it('can run again without duplicating anything', function () {
    $root = makeLegacySite();
    $this->artisan('legacy:import', ['path' => $root])->assertSuccessful();
    $dir = Menu::sole()->storage_dir;

    $this->artisan('legacy:import', ['path' => $root])->assertSuccessful();

    expect(Menu::count())->toBe(1)
        ->and(Menu::sole()->storage_dir)->toBe($dir)
        ->and(MenuSection::count())->toBe(35)
        ->and(MenuSectionBox::count())->toBe(36)
        ->and(Promo::count())->toBe(3)
        ->and(Holiday::count())->toBe(14)
        ->and(GalleryPhoto::count())->toBe(10);
});

it('archives the previously published menu', function () {
    $old = Menu::factory()->create(['status' => MenuStatus::Published]);

    $this->artisan('legacy:import', ['path' => makeLegacySite(false)])->assertSuccessful();

    expect($old->fresh()->status)->toBe(MenuStatus::Archived)
        ->and(Menu::published()->sole()->name)->toBe(LEGACY_MENU);
});

it('imports data and warns when files are missing', function () {
    $this->artisan('legacy:import', ['path' => makeLegacySite(false)])
        ->expectsOutputToContain('Нет файла assets/img/menu/'.LEGACY_MENU.'-1.jpg')
        ->expectsOutputToContain('записан A3')
        ->assertSuccessful();

    $menu = Menu::sole();

    expect($menu->source_pdf_path)->toBeNull()
        ->and([$menu->sheet_w_pt, $menu->sheet_h_pt])->toBe([841.89, 1190.55])
        ->and(GalleryPhoto::where('slug', 'fasad')->value('variants'))->toBe([]);
});

it('fails on a folder that is not the old site', function () {
    $this->artisan('legacy:import', ['path' => sys_get_temp_dir()])
        ->expectsOutputToContain('Не найден data/menu.php')
        ->assertFailed();

    expect(Menu::count())->toBe(0);
});
