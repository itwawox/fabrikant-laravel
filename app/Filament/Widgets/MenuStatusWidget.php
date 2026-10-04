<?php

namespace App\Filament\Widgets;

use App\Enums\MenuStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Menus\MenuResource;
use App\Models\Menu;
use App\Models\User;
use App\Support\Plural;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Дашборд: какое меню у гостей и не пора ли его обновить (напоминание через 60 дней). */
class MenuStatusWidget extends StatsOverviewWidget
{
    public const STALE_DAYS = 60;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canManage(UserRole::MENU);
    }

    protected function getStats(): array
    {
        $menu = Menu::published()->latest('published_at')->first();
        $processing = Menu::where('status', MenuStatus::Processing)->count();
        $ready = Menu::where('status', MenuStatus::Ready)->first();

        if ($menu === null) {
            return [Stat::make('Меню', 'не опубликовано')->color('danger')->url(MenuResource::getUrl())];
        }
        $days = (int) $menu->published_at->diffInDays(now());

        return [
            Stat::make('Меню у гостей', $menu->season)
                ->description('опубликовано '.$menu->published_at->translatedFormat('j F Y').', разделов: '.$menu->sections()->count())
                ->url(MenuResource::getUrl('edit', ['record' => $menu])),
            Stat::make('Меню обновлено', $days.' '.Plural::ru($days, 'день', 'дня', 'дней').' назад')
                ->description($days >= self::STALE_DAYS ? 'Пора проверить цены и загрузить новое меню' : 'Напомним через '.(self::STALE_DAYS - $days).' дн.')
                ->color($days >= self::STALE_DAYS ? 'warning' : 'success'),
            Stat::make('Новое меню', $processing ? 'обрабатывается' : ($ready ? '«'.$ready->season.'» готово' : '—'))
                ->description($ready?->scheduled_at ? 'опубликуется '.$ready->scheduled_at->translatedFormat('j F в H:i') : ($ready ? 'ждёт публикации' : 'загрузить — в разделе «Меню»'))
                ->url($ready ? MenuResource::getUrl('edit', ['record' => $ready]) : MenuResource::getUrl('create')),
        ];
    }
}
