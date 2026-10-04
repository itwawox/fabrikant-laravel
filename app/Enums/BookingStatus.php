<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Declined = 'declined';
    case Done = 'done';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Confirmed => 'Подтверждена',
            self::Declined => 'Отклонена',
            self::Done => 'Гость пришёл',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::Confirmed => 'success',
            self::Declined => 'danger',
            self::Done => 'gray',
        };
    }
}
