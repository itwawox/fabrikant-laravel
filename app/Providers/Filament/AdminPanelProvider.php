<?php

namespace App\Providers\Filament;

use App\Support\QueueHealth;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->brandName('ФабрикантЪ')
            // Фирменный вид: логотип сайта и его фавикон; ресурсы, права и маршруты не трогаем
            ->brandLogo(fn () => view('filament.brand-logo', ['color' => '#58211c']))
            ->darkModeBrandLogo(fn () => view('filament.brand-logo', ['color' => '#cbb27c']))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('assets/favicon/favicon.png'))
            ->navigationGroups(['Акции', 'Галерея', 'Сайт'])
            // Очередь задач стоит — предупреждение вверху любой страницы, а не вечная «Обработка…»
            ->renderHook(PanelsRenderHook::PAGE_START, fn (): string => QueueHealth::stalled()
                ? view('filament.queue-stalled')->render()
                : '')
            // Основной цвет — глубокое золото кнопки брони на сайте: на кнопках админки с белым текстом
            // светлое золото сайта (#cbb27c) не читалось бы
            ->colors([
                'primary' => Color::hex('#a8823a'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
