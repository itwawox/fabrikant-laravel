<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use App\Models\Page;
use App\Support\PageTexts;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    public function mount(): void
    {
        // Строка на каждую страницу сайта: пустые поля — тексты по умолчанию
        foreach (array_keys(PageTexts::PAGES) as $key) {
            Page::firstOrCreate(['key' => $key]);
        }
        parent::mount();
    }
}
