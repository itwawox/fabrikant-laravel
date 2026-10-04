<?php

namespace App\Filament\Resources\Holidays\Pages;

use App\Filament\Resources\Holidays\HolidayResource;
use App\Models\Holiday;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageHolidays extends ManageRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('official')
                ->label('Добавить официальные праздники РФ')
                ->color('gray')
                ->icon('heroicon-o-flag')
                ->requiresConfirmation()
                ->modalDescription('Добавятся нерабочие праздники по Трудовому кодексу, которых ещё нет в списке. Уже заведённые дни не изменятся.')
                ->action(function () {
                    $added = 0;
                    foreach (Holiday::OFFICIAL as $monthDay => $title) {
                        $added += Holiday::firstOrCreate(['month_day' => $monthDay], ['title' => $title])->wasRecentlyCreated ? 1 : 0;
                    }
                    Notification::make()->success()->title($added ? "Добавлено праздников: {$added}" : 'Все официальные праздники уже в списке')->send();
                }),
            CreateAction::make()->label('Добавить день'),
        ];
    }
}
