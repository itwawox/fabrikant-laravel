<?php

namespace App\Filament\Resources\Bookings;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Concerns\RestrictedToArea;
use App\Filament\Resources\Bookings\Pages\ManageBookings;
use App\Models\Booking;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Заявки на бронь с сайта. Создаёт их только гость, здесь — звонок и отметка, чем закончилось. */
class BookingResource extends Resource
{
    use RestrictedToArea;

    protected static string $area = UserRole::BOOKINGS;

    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $modelLabel = 'заявку';

    protected static ?string $pluralModelLabel = 'Брони';

    protected static ?int $navigationSort = 2;

    // Сколько новых заявок ждут звонка — видно прямо в меню
    public static function getNavigationBadge(): ?string
    {
        $count = Booking::where('status', BookingStatus::New)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        $status = fn (BookingStatus $to, string $label, string $icon) => Action::make($to->value)
            ->label($label)
            ->icon($icon)
            ->color($to->getColor())
            ->visible(fn (Booking $record): bool => $record->status !== $to)
            ->action(fn (Booking $record) => $record->update(['status' => $to]));

        return $table
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->columns([
                TextColumn::make('date')
                    ->label('Когда')
                    ->state(fn (Booking $record): string => $record->when())
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('date', $direction)->orderBy('time', $direction))
                    ->weight('bold'),
                TextColumn::make('guests')->label('Гостей')->numeric(),
                TextColumn::make('name')->label('Имя')->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->url(fn (Booking $record): string => 'tel:'.preg_replace('/[^\d+]/', '', $record->phone)),
                TextColumn::make('comment')->label('Комментарий')->limit(60)->tooltip(fn (Booking $record): ?string => $record->comment)->placeholder('—'),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('created_at')->label('Заявка')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(BookingStatus::class),
                TernaryFilter::make('upcoming')
                    ->label('Дата визита')
                    ->placeholder('Все')
                    ->trueLabel('Сегодня и позже')
                    ->falseLabel('Прошедшие')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereDate('date', '>=', today()),
                        false: fn (Builder $query) => $query->whereDate('date', '<', today()),
                    ),
            ])
            ->recordActions([
                $status(BookingStatus::Confirmed, 'Подтвердить', 'heroicon-o-check'),
                ActionGroup::make([
                    $status(BookingStatus::Declined, 'Отклонить', 'heroicon-o-x-mark'),
                    $status(BookingStatus::Done, 'Гость пришёл', 'heroicon-o-user'),
                    $status(BookingStatus::New, 'Вернуть в новые', 'heroicon-o-arrow-uturn-left'),
                    DeleteAction::make()->modalDescription('Заявка и данные гостя удалятся насовсем.'),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBookings::route('/'),
        ];
    }
}
