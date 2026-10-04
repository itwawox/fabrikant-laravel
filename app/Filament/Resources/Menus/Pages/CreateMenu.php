<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Services\Menu\MenuPipeline;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;

    protected static ?string $title = 'Новое меню';

    protected static bool $canCreateAnother = false;

    /** @param  array<string, mixed>  $data */
    protected function handleRecordCreation(array $data): Model
    {
        return app(MenuPipeline::class)->create(
            season: $data['season'],
            upload: $data['upload'],
            carryOver: (bool) ($data['carry_over'] ?? true),
            scheduledAt: filled($data['scheduled_at'] ?? null) ? Carbon::parse($data['scheduled_at']) : null,
            userId: auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return MenuResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'PDF загружен — меню обрабатывается';
    }
}
