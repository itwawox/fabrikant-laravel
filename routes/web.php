<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;

// Страницы сайта кэшируются целиком; правки в админке сбрасывают кэш (AppServiceProvider)
Route::middleware(CacheResponse::class)->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/menu', [MenuController::class, 'show'])->name('menu');
    Route::get('/gallery', [PageController::class, 'gallery'])->name('gallery');
    Route::get('/promos', [PageController::class, 'promos'])->name('promos');
    Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
});

// Бронь онлайн и политика обработки персональных данных — без кэша страниц (форма, сообщения об ошибках)
Route::get('/booking', [BookingController::class, 'show'])->name('booking');
Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
Route::get('/privacy', [BookingController::class, 'privacy'])->name('privacy');
Route::get('/consent', [BookingController::class, 'consent'])->name('consent');

// Предпросмотр меню до публикации: ссылку с подписью выдаёт админка
Route::get('/menu/preview/{menu}', [MenuController::class, 'preview'])->middleware('signed')->name('menu.preview');

// Адреса старого сайта. Они напечатаны в QR-кодах и разосланы в соцсетях, поэтому ведут на новые навсегда (301).
// Якорь (#supy, #happyhours, #foto-fasad) браузер сохраняет сам — сервер его не видит.
foreach ([
    'index.php' => '/',
    'about.php' => '/about',
    'menu.php' => '/menu',
    'gallery.php' => '/gallery',
    'calendar.php' => '/promos',
    'contacts.php' => '/contacts',
] as $old => $new) {
    Route::permanentRedirect($old, $new);
}

// Старые ссылки на PDF меню (/uploads/menu_…-web.pdf) ведут на текущее меню: файл прошлого сезона гостю не нужен
Route::get('/uploads/{file}', [MenuController::class, 'legacyPdf'])->where('file', '[A-Za-z0-9_.-]+\.pdf');
