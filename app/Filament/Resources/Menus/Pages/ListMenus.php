<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Services\Menu\MenuPdfException;
use App\Services\Menu\MenuPublisher;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        $previous = app(MenuPublisher::class)->previous();

        return [
            Action::make('rollback')
                ->label('Откатить на прошлое меню')
                ->color('gray')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible($previous !== null)
                ->requiresConfirmation()
                ->modalDescription($previous ? "Гости снова увидят меню «{$previous->season}». Текущее уйдёт в архив — его можно будет опубликовать обратно." : null)
                ->action(function () {
                    try {
                        $menu = app(MenuPublisher::class)->rollback();
                        Notification::make()->success()->title("Опубликовано меню «{$menu->season}»")->send();
                    } catch (MenuPdfException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();
                    }
                }),
            CreateAction::make()->label('Загрузить новое меню'),
        ];
    }
}
