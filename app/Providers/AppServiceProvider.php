<?php

namespace App\Providers;

use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use App\Models\Holiday;
use App\Models\Menu;
use App\Models\MenuPage;
use App\Models\MenuSection;
use App\Models\MenuSectionBox;
use App\Models\Promo;
use App\Models\PromoBlackout;
use Illuminate\Support\ServiceProvider;
use Spatie\ResponseCache\Facades\ResponseCache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Модели, из которых собираются страницы сайта: их правка сбрасывает кэш страниц.
     */
    private const SITE_MODELS = [
        Menu::class, MenuPage::class, MenuSection::class, MenuSectionBox::class,
        Promo::class, Holiday::class, PromoBlackout::class,
        GalleryRubric::class, GalleryPhoto::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Страниц шесть, правки редкие — проще сбросить кэш целиком, чем вычислять, какие страницы задеты
        foreach (self::SITE_MODELS as $model) {
            $model::saved(fn () => ResponseCache::clear());
            $model::deleted(fn () => ResponseCache::clear());
        }
    }
}
