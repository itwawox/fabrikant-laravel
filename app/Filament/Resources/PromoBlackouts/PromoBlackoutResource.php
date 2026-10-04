<?php

namespace App\Filament\Resources\PromoBlackouts;

use App\Filament\Resources\PromoBlackouts\Pages\ManagePromoBlackouts;
use App\Models\PromoBlackout;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PromoBlackoutResource extends Resource
{
    protected static ?string $model = PromoBlackout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static string|UnitEnum|null $navigationGroup = 'Акции';

    protected static ?string $modelLabel = 'день без акций';

    protected static ?string $pluralModelLabel = 'Дни без акций';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')
                ->label('Дата')
                ->native(false)
                ->displayFormat('j F Y')
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('reason')
                ->label('Причина')
                ->placeholder('Концерт группы …, перенос выходного')
                ->maxLength(150),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Концерты, переносы выходных: в эти дни не идут акции с отметкой «не действует в праздники».')
            ->defaultSort('date')
            ->columns([
                TextColumn::make('date')->label('Дата')->date('j F Y, l')->sortable(),
                TextColumn::make('reason')->label('Причина')->placeholder('—'),
            ])
            ->filters([
                // Прошедшие дни ни на что не влияют — по умолчанию их не показываем
                TernaryFilter::make('upcoming')
                    ->label('Какие дни')
                    ->placeholder('Все')
                    ->trueLabel('Предстоящие')
                    ->falseLabel('Прошедшие')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereDate('date', '>=', today()),
                        false: fn (Builder $query) => $query->whereDate('date', '<', today()),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePromoBlackouts::route('/'),
        ];
    }
}
