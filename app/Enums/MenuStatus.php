<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MenuStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Ready = 'ready';
    case Published = 'published';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Processing => 'Обработка',
            self::Ready => 'Готово',
            self::Published => 'Опубликовано',
            self::Archived => 'Архив',
        };
    }
}
