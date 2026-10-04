<?php

namespace App\Filament\Concerns;

use App\Models\User;

/**
 * Раздел админки виден и открывается только ролям, которым он положен (UserRole::areas()).
 * В классе ресурса или страницы задаётся protected static string $area = UserRole::…
 */
trait RestrictedToArea
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->canManage(static::$area);
    }
}
