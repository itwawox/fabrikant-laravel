<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Роли в админке (ТЗ 5.7): владелец — всё; менеджер — меню, акции, галерея, брони; SMM — галерея и тексты.
 */
enum UserRole: string implements HasDescription, HasLabel
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Smm = 'smm';

    // Разделы админки
    public const MENU = 'menu';

    public const PROMOS = 'promos';

    public const GALLERY = 'gallery';

    public const BOOKINGS = 'bookings';

    public const CONTENT = 'content';

    public const SETTINGS = 'settings';

    public const USERS = 'users';

    public const LOG = 'log';

    /** @return list<string> */
    public function areas(): array
    {
        return match ($this) {
            self::Owner => [self::MENU, self::PROMOS, self::GALLERY, self::BOOKINGS, self::CONTENT, self::SETTINGS, self::USERS, self::LOG],
            self::Manager => [self::MENU, self::PROMOS, self::GALLERY, self::BOOKINGS],
            self::Smm => [self::GALLERY, self::CONTENT],
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Владелец',
            self::Manager => 'Менеджер',
            self::Smm => 'SMM',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Owner => 'Всё, включая настройки сайта, пользователей и журнал',
            self::Manager => 'Меню, акции, галерея, брони',
            self::Smm => 'Галерея и тексты страниц',
        };
    }
}
