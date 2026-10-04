<?php

namespace App\Http\Controllers;

use App\Models\GalleryPhoto;
use App\Models\Menu;
use App\Models\Promo;
use Illuminate\Http\Response;

// Карта сайта для поисковиков: шесть страниц, дата изменения — по данным, из которых страница собрана
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $menu = Menu::published()->max('updated_at');
        $pages = [
            'home' => null,
            'about' => null,
            'menu' => $menu,
            'gallery' => GalleryPhoto::max('updated_at'),
            'promos' => Promo::max('updated_at'),
            'contacts' => null,
        ];

        return response()
            ->view('sitemap', ['pages' => $pages])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
