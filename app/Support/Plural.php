<?php

namespace App\Support;

/** Русское склонение после числа: 1 день, 2 дня, 5 дней, 11 дней, 21 день. */
class Plural
{
    public static function ru(int $n, string $one, string $few, string $many): string
    {
        $mod100 = $n % 100;
        $mod10 = $n % 10;

        return match (true) {
            $mod100 >= 11 && $mod100 <= 14 => $many,
            $mod10 === 1 => $one,
            $mod10 >= 2 && $mod10 <= 4 => $few,
            default => $many,
        };
    }
}
