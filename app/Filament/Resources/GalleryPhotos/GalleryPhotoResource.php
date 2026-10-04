<?php

namespace App\Filament\Resources\GalleryPhotos;

use App\Enums\UserRole;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\GalleryPhotos\Pages\CreateGalleryPhoto;
use App\Filament\Resources\GalleryPhotos\Pages\EditGalleryPhoto;
use App\Filament\Resources\GalleryPhotos\Pages\ListGalleryPhotos;
use App\Filament\Resources\GalleryPhotos\Schemas\GalleryPhotoForm;
use App\Filament\Resources\GalleryPhotos\Tables\GalleryPhotosTable;
use App\Models\GalleryPhoto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GalleryPhotoResource extends Resource
{
    use RestrictedToArea;

    protected static string $area = UserRole::GALLERY;

    protected static ?string $model = GalleryPhoto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Галерея';

    protected static ?string $modelLabel = 'фото';

    protected static ?string $pluralModelLabel = 'Фото';

    protected static ?string $recordTitleAttribute = 'caption';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return GalleryPhotoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GalleryPhotosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGalleryPhotos::route('/'),
            'create' => CreateGalleryPhoto::route('/create'),
            'edit' => EditGalleryPhoto::route('/{record}/edit'),
        ];
    }
}
