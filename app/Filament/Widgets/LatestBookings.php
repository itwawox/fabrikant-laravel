<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Дашборд: последние заявки на бронь. Виден, только если онлайн-бронь хоть раз включали. */
class LatestBookings extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Последние заявки на бронь';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canManage(UserRole::BOOKINGS) && Booking::exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Booking::query()->latest()->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('when')->label('Когда')->state(fn (Booking $r): string => $r->when()),
                TextColumn::make('guests')->label('Гостей'),
                TextColumn::make('name')->label('Имя'),
                TextColumn::make('phone')->label('Телефон'),
                TextColumn::make('status')->label('Статус')->badge(),
            ])
            ->recordUrl(fn () => BookingResource::getUrl())
            ->emptyStateHeading('Заявок нет');
    }
}
