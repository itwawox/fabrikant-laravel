<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Support\PageTexts;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

/**
 * @property Page $record
 */
class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return PageTexts::PAGES[$this->record->key]['label'];
    }

    /** В форме — текущие тексты сайта, даже если страницу ещё не правили */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $defaults = PageTexts::PAGES[$this->record->key];
        $data['seo_title'] = $data['seo_title'] ?: $defaults['seo_title'];
        $data['seo_description'] = $data['seo_description'] ?: $defaults['seo_description'];
        $data['content'] = array_replace_recursive($defaults['content'], $data['content'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['og_image'] ?? null) !== $this->record->og_image && $this->record->og_image) {
            Storage::disk('public')->delete($this->record->og_image);
        }

        return $data;
    }
}
