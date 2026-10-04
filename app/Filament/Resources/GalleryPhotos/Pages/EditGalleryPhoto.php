<?php

namespace App\Filament\Resources\GalleryPhotos\Pages;

use App\Filament\Resources\GalleryPhotos\GalleryPhotoResource;
use App\Jobs\Gallery\BuildGalleryPhoto;
use App\Models\GalleryPhoto;
use App\Services\Gallery\GalleryImages;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

/**
 * @property GalleryPhoto $record
 */
class EditGalleryPhoto extends EditRecord
{
    protected static string $resource = GalleryPhotoResource::class;

    private bool $rebuild = false;

    public function getTitle(): string
    {
        return 'Фото «'.$this->record->caption.'»';
    }

    /** Опрос из превью, пока копии режутся */
    public function refreshPhoto(): void
    {
        $this->record->refresh();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['upload'])) {
            $old = $this->record->source_path;
            $data['source_path'] = CreateGalleryPhoto::keepSource((string) $data['upload'], (string) $data['slug']);
            if ($old) {
                Storage::disk('local')->delete($old);
            }
            $this->rebuild = true;
        }
        unset($data['upload']);
        // Новый адрес — новые имена файлов копий
        $this->rebuild = $this->rebuild || $data['slug'] !== $this->record->slug;

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->rebuild) {
            BuildGalleryPhoto::dispatch($this->record);
        }
        $this->data['upload'] = [];
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalDescription('Фото пропадёт с сайта вместе со всеми копиями.')
                ->after(fn (GalleryPhoto $record) => app(GalleryImages::class)->delete($record)),
        ];
    }
}
