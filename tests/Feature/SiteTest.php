<?php

use App\Enums\MenuStatus;
use App\Enums\PhotoSize;
use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use App\Models\Menu;
use App\Models\MenuPage;
use App\Models\MenuSection;
use App\Models\MenuSectionBox;
use App\Models\Promo;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

// Опубликованное меню из двух страниц: на первой разделы «Супы» (две рамки) и «Салаты»
function publishedMenu(): Menu
{
    $menu = Menu::factory()->create([
        'status' => MenuStatus::Published,
        'published_at' => now(),
        'storage_dir' => 'menus/1-abc',
        'web_pdf_path' => 'menus/1-abc/web.pdf',
    ]);
    foreach ([1 => 'Закуски, салаты, супы', 2 => 'Горячее и гриль'] as $n => $title) {
        MenuPage::factory()->for($menu)->create(['number' => $n, 'title' => $title]);
        foreach (['1600.jpg', '1600.webp', '1000.webp', '200.webp', '3200.webp'] as $suffix) {
            Storage::disk('public')->put("menus/1-abc/{$n}-{$suffix}", 'x');
        }
    }
    Storage::disk('public')->put('menus/1-abc/web.pdf', str_repeat('x', 4_300_000));
    $soups = MenuSection::factory()->for($menu)->create(['title' => 'Супы', 'slug' => 'supy', 'position' => 0]);
    MenuSectionBox::factory()->for($soups, 'section')->create(['x' => 0.655, 'y' => 0.081, 'w' => 0.29, 'h' => 0.272]);
    MenuSectionBox::factory()->for($soups, 'section')->create(['x' => 0.05, 'y' => 0.5, 'w' => 0.4, 'h' => 0.2, 'position' => 1]);
    MenuSection::factory()->for($menu)->create(['title' => 'Салаты', 'slug' => 'salaty', 'position' => 1]);

    return $menu;
}

it('serves every page', function (string $url) {
    publishedMenu();

    $this->get($url)->assertOk()->assertSee('<html lang="ru">', false);
})->with(['/', '/about', '/menu', '/gallery', '/promos', '/contacts']);

it('marks the current page in the navigation', function () {
    $this->get('/contacts')->assertSee('<a href="/contacts" aria-current="page">Контакты</a>', false);
});

it('redirects old addresses permanently', function (string $old, string $new) {
    $this->get($old)->assertStatus(301)->assertRedirect($new);
})->with([
    ['/index.php', '/'],
    ['/about.php', '/about'],
    ['/menu.php', '/menu'],
    ['/gallery.php', '/gallery'],
    ['/calendar.php', '/promos'],
    ['/contacts.php', '/contacts'],
]);

it('sends old menu PDF links to the current menu', function () {
    publishedMenu();

    $this->get('/uploads/menu_restaurant_fabrikant2026_leto-web.pdf')
        ->assertStatus(301)
        ->assertRedirect('/storage/menus/1-abc/web.pdf');
});

it('shows the custom not found page', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Такой страницы у нас нет')
        ->assertSee('<meta name="robots" content="noindex">', false)
        ->assertDontSee('rel="canonical"', false);
});

it('renders the published menu with sections and QR anchors', function () {
    publishedMenu();

    $this->get('/menu')
        ->assertSee('<p class="menu-head__season">Осень 2026</p>', false)
        ->assertSee('id="supy"', false)
        ->assertDontSee('href="#salaty"', false) // у «Салатов» нет рамок
        ->assertSee('style="--x:0.655;--y:0.081;--w:0.290;--h:0.272"', false)
        ->assertSee('data-box="0.050,0.500,0.400,0.200"', false)
        ->assertSee('data-src-zoom="/storage/menus/1-abc/{n}-3200.webp"', false)
        ->assertSee('href="/storage/menus/1-abc/web.pdf" download="Меню ФабрикантЪ, осень 2026.pdf"', false)
        ->assertSee('Скачать PDF, 4,1 МБ', false)
        ->assertSee('"hasMenu":{"@type":"Menu","name":"Меню, осень 2026","url":"'.url('/menu').'"', false);
});

it('falls back to JPEG pages when WebP copies are missing', function () {
    publishedMenu();
    Storage::disk('public')->delete('menus/1-abc/2-1000.webp');

    $this->get('/menu')->assertSee('data-src-low="/storage/menus/1-abc/{n}-1600.jpg"', false);
});

it('answers 503 while no menu is published', function () {
    $this->get('/menu')->assertStatus(503);
});

it('shows active promos with the live bar', function () {
    $this->travelTo(new DateTimeImmutable('2026-10-05 13:00', new DateTimeZone('Europe/Simferopol')));
    Promo::factory()->create(['slug' => 'happyhours', 'title' => 'Счастливые часы', 'photo_path' => 'promos/a.jpg', 'photo_webp_path' => 'promos/a.webp']);
    Promo::factory()->create(['slug' => 'off', 'title' => 'Выключенная', 'is_active' => false]);

    $this->get('/promos')
        ->assertSee('<article class="promo" id="happyhours">', false)
        ->assertSee('srcset="/storage/promos/a.webp"', false)
        ->assertDontSee('Выключенная')
        ->assertSee('<li class="promo-now__item is-live"><a class="promo-now__link" href="/promos#happyhours"><span class="promo-now__when">Сейчас, до 15:00</span>', false);
});

it('hides the promo bar when nothing is scheduled', function () {
    $this->get('/promos')->assertSee('<aside class="promo-now" aria-label="Акции сегодня" data-promos="', false)
        ->assertSee('}" hidden><ul class="promo-now__list"></ul></aside>', false);
});

it('lays out gallery photos with anchors and variants', function () {
    $rubric = GalleryRubric::factory()->create(['key' => 'dom', 'title' => 'Ресторан']);
    GalleryPhoto::factory()->for($rubric, 'rubric')->create([
        'slug' => 'fasad',
        'size' => PhotoSize::Lead,
        'variants' => ['webp' => [480 => 'gallery/fasad-480.webp', 1024 => 'gallery/fasad-1024.webp'], 'jpg' => 'gallery/fasad-1024.jpg'],
    ]);
    // Фото без копий на странице не выводится
    GalleryPhoto::factory()->for($rubric, 'rubric')->create(['slug' => 'bez-kopiy', 'variants' => []]);

    $this->get('/gallery')
        ->assertSee('<a href="#rubric-dom">Ресторан</a>', false)
        ->assertSee('<figure class="plate plate--lead" id="foto-fasad">', false)
        ->assertSee('srcset="/storage/gallery/fasad-480.webp 480w, /storage/gallery/fasad-1024.webp 1024w"', false)
        ->assertDontSee('foto-bez-kopiy')
        ->assertSee('<span class="viewer__current">1</span> / 1', false);
});

it('lists pages in the sitemap', function () {
    publishedMenu();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
        ->assertSee('<loc>'.url('/menu').'</loc>', false)
        ->assertSee('<lastmod>', false);
});

it('caches pages and drops the cache when data changes', function () {
    $promo = Promo::factory()->create(['title' => 'Счастливые часы']);

    $this->get('/promos')->assertSee('Счастливые часы');
    // Прямо в базе, мимо модели: кэш об этом не знает и отдаёт прежнюю страницу
    Promo::whereKey($promo->id)->toBase()->update(['title' => 'Изменено в обход']);
    $this->get('/promos')->assertSee('Счастливые часы');

    // Правка через модель (как в админке) сбрасывает кэш
    $promo->fresh()->update(['title' => 'Обеденные часы']);
    $this->get('/promos')->assertSee('Обеденные часы')->assertDontSee('Счастливые часы');
});

it('shows a promo without a photo', function () {
    Promo::factory()->create(['slug' => 'bez-foto', 'photo_path' => null]);

    $this->get('/promos')
        ->assertOk()
        ->assertSee('<article class="promo" id="bez-foto">', false)
        ->assertDontSee('promo__photo', false);
});

it('shows the vector logo in the header and the menu ornaments in the footer', function () {
    $this->get('/contacts')
        ->assertSee('<svg class="navbar-brand__logo" focusable="false" role="img" aria-label="ФабрикантЪ — на главную"', false)
        ->assertSee('class="footer-ornament__rule"', false)
        ->assertSee('Каждый день с 11:00 до 23:00');
    $this->get('/')->assertSee('class="welcome-logo"', false);
});

it('carries a hidden cookie note that links to the privacy policy', function () {
    // Показывает её site.js, пока нет отметки «Понятно»: страница кэшируется целиком и одинакова для всех
    $this->get('/promos')
        ->assertSee('<aside class="cookie-note" id="cookie-note" aria-label="Файлы cookie" hidden>', false)
        ->assertSee('href="'.route('privacy').'"', false);
});
