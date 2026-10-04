<?php

namespace App\Models;

use App\Models\Concerns\LogsChanges;
use Database\Factories\PromoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'slug', 'title', 'discount', 'subject', 'short', 'photo_path', 'photo_webp_path', 'alt', 'rows', 'terms',
    'position', 'is_active', 'days', 'time_from', 'time_to', 'not_holidays', 'valid_from', 'valid_to',
])]
class Promo extends Model
{
    /** @use HasFactory<PromoFactory> */
    use HasFactory;

    use LogsChanges;

    protected function casts(): array
    {
        return [
            'rows' => 'array',
            'terms' => 'array',
            'days' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
            'not_holidays' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    // Без дней недели акция не привязана ко времени и в плашке «Сейчас действует» не появляется
    public function hasSchedule(): bool
    {
        return ! empty($this->days);
    }

    // Адрес фото карточки от корня сайта: webp — для современных браузеров, jpg — запасной
    public function photoUrl(string $format = 'jpg'): ?string
    {
        $path = $format === 'webp' ? $this->photo_webp_path : $this->photo_path;

        return $path ? Storage::disk('public')->url($path) : null;
    }
}
