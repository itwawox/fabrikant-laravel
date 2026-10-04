<?php

namespace App\Models;

use Database\Factories\MenuSectionBoxFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Рамка раздела на листе: x, y, ширина и высота в долях листа (0…1), три знака после запятой.
#[Fillable(['section_id', 'x', 'y', 'w', 'h', 'position'])]
class MenuSectionBox extends Model
{
    /** @use HasFactory<MenuSectionBoxFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'x' => 'float',
            'y' => 'float',
            'w' => 'float',
            'h' => 'float',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<MenuSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(MenuSection::class, 'section_id');
    }

    /** @return array{0: float, 1: float, 2: float, 3: float} */
    public function toBox(): array
    {
        return [$this->x, $this->y, $this->w, $this->h];
    }
}
