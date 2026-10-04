<?php

namespace App\Filament\Resources\PromoBlackouts\Pages;

use App\Filament\Resources\PromoBlackouts\PromoBlackoutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePromoBlackouts extends ManageRecords
{
    protected static string $resource = PromoBlackoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить день'),
        ];
    }
}
