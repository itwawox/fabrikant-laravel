<?php

namespace App\Filament\Resources\GalleryRubrics;

use App\Enums\UserRole;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\GalleryRubrics\Pages\ManageGalleryRubrics;
use App\Models\GalleryRubric;
use App\Support\MenuSlug;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class GalleryRubricResource extends Resource
{
    use RestrictedToArea;

    protected static string $area = UserRole::GALLERY;

    protected static ?string $model = GalleryRubric::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Галерея';

    protected static ?string $modelLabel = 'рубрику';

    protected static ?string $pluralModelLabel = 'Рубрики';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')->label('Название')->placeholder('Летний сад')->required()->maxLength(60),
            Textarea::make('lede')->label('Подпись под названием')->rows(2)->maxLength(300),
            TextInput::make('key')
                ->label('Служебное имя')
                ->helperText('Латиницей; если пусто — из названия.')
                ->maxLength(40)
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (?string $state, Get $get): string => MenuSlug::make($state ?: (string) $get('title'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Порядок рубрик на странице «Галерея» — перетаскиванием. Рубрика без фото на сайте не видна.')
            ->reorderable('position')
            ->defaultSort('position')
            ->paginated(false)
            ->columns([
                TextColumn::make('title')->label('Рубрика')->weight('bold')->description(fn (GalleryRubric $r): ?string => $r->lede),
                TextColumn::make('photos_count')->label('Фото')->counts('photos'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (GalleryRubric $record): bool => ! $record->photos()->exists())
                    ->modalDescription('Рубрика пустая — удалить её можно без последствий.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageGalleryRubrics::route('/'),
        ];
    }
}
