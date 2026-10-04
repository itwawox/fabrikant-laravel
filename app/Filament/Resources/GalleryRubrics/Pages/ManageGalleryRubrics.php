<?php

namespace App\Filament\Resources\GalleryRubrics\Pages;

use App\Filament\Resources\GalleryRubrics\GalleryRubricResource;
use App\Models\GalleryRubric;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageGalleryRubrics extends ManageRecords
{
    protected static string $resource = GalleryRubricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Добавить рубрику')
                ->mutateDataUsing(fn (array $data): array => $data + ['position' => (int) GalleryRubric::max('position') + 1]),
        ];
    }
}
