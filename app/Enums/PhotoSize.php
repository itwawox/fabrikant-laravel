<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

// Место фото в сетке галереи. На кадрирование не влияет: фото всегда показывается целиком.
enum PhotoSize: string implements HasLabel
{
    case Lead = 'lead';
    case Std = 'std';
    case Tall = 'tall';

    public function getLabel(): string
    {
        return match ($this) {
            self::Lead => 'Широкое (главное в рубрике)',
            self::Std => 'Обычное',
            self::Tall => 'Вертикальное',
        };
    }
}
