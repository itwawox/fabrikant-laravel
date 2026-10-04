<?php

namespace App\Filament\Resources\Menus;

use App\Enums\UserRole;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Resources\Menus\Pages\MenuSections;
use App\Filament\Resources\Menus\Schemas\MenuForm;
use App\Filament\Resources\Menus\Tables\MenusTable;
use App\Models\Menu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    use RestrictedToArea;

    protected static string $area = UserRole::MENU;

    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $modelLabel = 'меню';

    protected static ?string $pluralModelLabel = 'Меню';

    protected static ?string $recordTitleAttribute = 'season';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenusTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
            'sections' => MenuSections::route('/{record}/sections'),
        ];
    }
}
