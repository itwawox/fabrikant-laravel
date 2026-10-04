<?php

namespace App\Filament\Resources\Promos\Pages;

use App\Filament\Resources\Promos\PromoResource;
use App\Filament\Resources\Promos\Schemas\PromoForm;
use App\Models\Promo;
use App\Services\Promos\PromoPhoto;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

/**
 * @property Promo $record
 */
class EditPromo extends EditRecord
{
    protected static string $resource = PromoResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return PromoForm::fill($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $upload = $data['photo_upload'] ?? null;
        unset($data['photo_upload']);
        if ($upload) {
            $data += app(PromoPhoto::class)->store($this->record, Storage::disk('local')->path($upload));
            Storage::disk('local')->delete($upload);
        }

        return PromoForm::dehydrate($data);
    }

    protected function afterSave(): void
    {
        // Поле загрузки очищаем: фото уже обработано и показано выше
        $this->data['photo_upload'] = [];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->after(fn (Promo $record) => app(PromoPhoto::class)->delete($record)),
        ];
    }
}
