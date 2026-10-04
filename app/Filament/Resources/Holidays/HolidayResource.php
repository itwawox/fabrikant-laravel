<?php

namespace App\Filament\Resources\Holidays;

use App\Filament\Resources\Holidays\Pages\ManageHolidays;
use App\Models\Holiday;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class HolidayResource extends Resource
{
    protected static ?string $model = Holiday::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Акции';

    protected static ?string $modelLabel = 'праздник';

    protected static ?string $pluralModelLabel = 'Праздники';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('month_day')
                ->label('День')
                ->helperText('Месяц-день без года: 05-09 — 9 мая. Праздник повторяется каждый год.')
                ->placeholder('05-09')
                ->mask('99-99')
                ->required()
                ->regex('/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/')
                ->validationMessages(['regex' => 'Нужно в виде ММ-ДД, например 05-09.'])
                ->unique(ignoreRecord: true),
            TextInput::make('title')
                ->label('Название')
                ->placeholder('День Победы')
                ->maxLength(100),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('В эти дни не идут акции с отметкой «не действует в праздники». Переносы выходных заводите в «Дни без акций».')
            ->defaultSort('month_day')
            ->paginated(false)
            ->columns([
                TextColumn::make('month_day')
                    ->label('День')
                    ->formatStateUsing(fn (Holiday $record): string => $record->label())
                    ->sortable(),
                TextColumn::make('title')->label('Название')->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHolidays::route('/'),
        ];
    }
}
