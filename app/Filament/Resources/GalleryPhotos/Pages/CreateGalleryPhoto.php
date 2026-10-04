<?php

namespace App\Filament\Resources\GalleryPhotos\Pages;

use App\Filament\Resources\GalleryPhotos\GalleryPhotoResource;
use App\Jobs\Gallery\BuildGalleryPhoto;
use App\Models\GalleryPhoto;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateGalleryPhoto extends CreateRecord
{
    protected static string $resource = GalleryPhotoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source_path'] = self::keepSource((string) $data['upload'], (string) $data['slug']);
        unset($data['upload']);
        // Новое фото — в конец рубрики
        $data['position'] = (int) GalleryPhoto::where('rubric_id', $data['rubric_id'])->max('position') + 1;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var GalleryPhoto $photo */
        $photo = $this->getRecord();
        BuildGalleryPhoto::dispatch($photo);
    }

    protected function getRedirectUrl(): string
    {
        return GalleryPhotoResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    /** Загруженный исходник — в постоянную папку на закрытом диске, со своим именем */
    public static function keepSource(string $upload, string $slug): string
    {
        $path = 'gallery/source/'.$slug.'-'.Str::lower(Str::random(6)).'.'.pathinfo($upload, PATHINFO_EXTENSION);
        Storage::disk('local')->move($upload, $path);

        return $path;
    }
}
