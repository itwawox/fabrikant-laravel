<?php

namespace App\Filament\Resources\GalleryPhotos\Schemas;

use App\Enums\PhotoSize;
use App\Models\GalleryPhoto;
use App\Support\MenuSlug;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class GalleryPhotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Фото')->columns(2)->schema([
                View::make('filament.gallery.photo')->hiddenOn('create'),
                FileUpload::make('upload')
                    ->label(fn (?GalleryPhoto $record): string => $record ? 'Заменить фото' : 'Фото')
                    ->helperText('JPEG, PNG или WebP в полном размере, до 30 МБ. Копии для сайта сделаются сами за полминуты.')
                    ->disk('local')
                    ->directory('gallery-uploads')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(30 * 1024)
                    ->required(fn (?GalleryPhoto $record): bool => $record === null),
            ]),
            Section::make('Где и как показывать')->columns(2)->schema([
                Select::make('rubric_id')
                    ->label('Рубрика')
                    ->relationship('rubric', 'title', fn ($query) => $query->orderBy('position'))
                    ->required()
                    ->preload(),
                ToggleButtons::make('size')
                    ->label('Место в сетке')
                    ->options(PhotoSize::class)
                    ->default(PhotoSize::Std)
                    ->inline()
                    ->required(),
                TextInput::make('caption')
                    ->label('Подпись')
                    ->placeholder('Фасад на Киевской вечером.')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),
                TextInput::make('alt')
                    ->label('Что на фото (для незрячих)')
                    ->placeholder('Фасад ресторана с вывеской и фонарями')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),
                TextInput::make('slug')
                    ->label('Адрес фото')
                    ->prefix('/gallery#foto-')
                    ->helperText('Латиницей. Если пусто — из подписи. По этой ссылке фото открывается сразу; после публикации лучше не менять.')
                    ->maxLength(40)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state, Get $get): string => substr(MenuSlug::make($state ?: (string) $get('caption')), 0, 40))
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
