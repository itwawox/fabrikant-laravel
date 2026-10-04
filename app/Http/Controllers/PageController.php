<?php

namespace App\Http\Controllers;

use App\Models\GalleryPhoto;
use App\Models\Promo;
use App\Services\PromoSchedule;
use App\Support\Press;
use Illuminate\View\View;

// Простые страницы сайта. Меню и галерея — в своих контроллерах: у них своя подготовка данных.
class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function about(): View
    {
        // В очерке о кухне — фото из галереи (каре ягнёнка); нет его копий — блок с фото не выводится
        return view('pages.about', [
            'kare' => Press::entry(GalleryPhoto::where('slug', 'kare')->first()),
        ]);
    }

    public function gallery(): View
    {
        return view('pages.gallery', Press::gallery());
    }

    public function contacts(): View
    {
        return view('pages.contacts');
    }

    public function promos(): View
    {
        return view('pages.promos', [
            // Акция с закончившимся периодом со страницы уходит сама
            'promos' => Promo::where('is_active', true)
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', today(PromoSchedule::TZ)->toDateString()))
                ->orderBy('position')->get(),
        ]);
    }
}
