<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Models\Page;
use App\Support\PageTexts;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Тексты и SEO страниц сайта. Страницы не создаются и не удаляются — их набор задан в PageTexts::PAGES.
 */
class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Сайт';

    protected static ?string $modelLabel = 'страницу';

    protected static ?string $pluralModelLabel = 'Страницы и тексты';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        $order = array_keys(PageTexts::PAGES);

        return $table
            ->paginated(false)
            ->modifyQueryUsing(fn ($query) => $query->whereIn('key', $order))
            ->defaultSort(fn ($query) => $query->orderByRaw('case key '.implode(' ', array_map(fn ($k, $i) => "when '{$k}' then {$i}", $order, array_keys($order))).' end'))
            ->columns([
                TextColumn::make('key')
                    ->label('Страница')
                    ->weight('bold')
                    ->formatStateUsing(fn (string $state): string => PageTexts::PAGES[$state]['label']),
                TextColumn::make('seo_title')
                    ->label('Заголовок во вкладке и в поиске')
                    ->state(fn (Page $record): string => PageTexts::for($record->key)->title())
                    ->wrap(),
                TextColumn::make('updated_at')->label('Изменено')->since(),
            ])
            ->recordActions([
                Action::make('site')
                    ->label('На сайте')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn (Page $record): bool => $record->key !== '404')
                    ->url(fn (Page $record): string => $record->key === 'home' ? url('/') : route($record->key))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
