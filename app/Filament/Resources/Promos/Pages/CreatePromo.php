<?php

namespace App\Filament\Resources\Promos\Pages;

use App\Filament\Resources\Promos\PromoResource;
use App\Filament\Resources\Promos\Schemas\PromoForm;
use App\Models\Promo;
use App\Services\Promos\PromoPhoto;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreatePromo extends CreateRecord
{
    protected static string $resource = PromoResource::class;

    protected static bool $canCreateAnother = false;

    /** @var string|null загруженное фото: обрабатывается, когда у акции уже есть адрес */
    private ?string $upload = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->upload = $data['photo_upload'] ?? null;
        unset($data['photo_upload']);
        // Новая акция — в конец списка
        $data['position'] = (int) Promo::max('position') + 1;

        return PromoForm::dehydrate($data);
    }

    protected function afterCreate(): void
    {
        if ($this->upload) {
            /** @var Promo $promo */
            $promo = $this->getRecord();
            $promo->update(app(PromoPhoto::class)->store($promo, Storage::disk('local')->path($this->upload)));
            Storage::disk('local')->delete($this->upload);
        }
    }

    protected function getRedirectUrl(): string
    {
        return PromoResource::getUrl();
    }
}
