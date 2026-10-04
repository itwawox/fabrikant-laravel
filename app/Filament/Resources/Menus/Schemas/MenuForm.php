<?php

namespace App\Filament\Resources\Menus\Schemas;

use App\Enums\MenuStatus;
use App\Models\Menu;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Состояние обработки: прогресс, ошибка, публикация. Пока меню обрабатывается — обновляется само
            View::make('filament.menus.status')->hiddenOn('create')->columnSpanFull(),

            TextInput::make('season')
                ->label('Сезон')
                ->placeholder('Зима 2026')
                ->helperText('Так меню называется в админке и в имени PDF, который скачивают гости.')
                ->required()
                ->maxLength(60),

            FileUpload::make('upload')
                ->label('PDF меню от типографии')
                ->helperText('Один файл PDF, до 100 МБ. Страницы, картинки и лёгкий PDF для скачивания сделаются сами.')
                ->disk('local')
                ->directory('menu-uploads')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(config('menu.max_upload_kb'))
                ->required()
                ->visibleOn('create')
                ->columnSpanFull(),

            Toggle::make('carry_over')
                ->label('Перенести подписи страниц и разделы из текущего меню')
                ->helperText('Сработает, если страниц столько же. Потом проверьте, не съехали ли рамки разделов.')
                ->default(true)
                ->visibleOn('create'),

            DateTimePicker::make('scheduled_at')
                ->label('Опубликовать автоматически')
                ->helperText('Необязательно. Можно опубликовать и вручную, когда проверите меню.')
                ->seconds(false)
                ->minDate(now()->startOfDay())
                ->visibleOn('create'),

            Section::make('Подписи страниц')
                ->description('Коротко и словами гостя: «Супы и салаты», «Горячее и гриль». Подписи видны в содержании и читаются незрячим.')
                ->hiddenOn('create')
                ->visible(fn (?Menu $record): bool => $record?->status !== MenuStatus::Processing && (bool) $record?->pages()->exists())
                ->columnSpanFull()
                ->schema([
                    Repeater::make('pages')
                        ->hiddenLabel()
                        ->relationship(modifyQueryUsing: fn ($query) => $query->orderBy('number'))
                        ->schema([
                            TextInput::make('title')
                                ->hiddenLabel()
                                ->required()
                                ->maxLength(120),
                        ])
                        ->itemLabel(fn (array $state): string => 'Страница '.($state['number'] ?? ''))
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->grid(2),
                ]),
        ]);
    }
}
