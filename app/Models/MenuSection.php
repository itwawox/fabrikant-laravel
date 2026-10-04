<?php

namespace App\Models;

use Database\Factories\MenuSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['menu_id', 'page_number', 'title', 'slug', 'slug_locked', 'position'])]
class MenuSection extends Model
{
    /** @use HasFactory<MenuSectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
            'slug_locked' => 'boolean',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return HasMany<MenuSectionBox, $this> */
    public function boxes(): HasMany
    {
        return $this->hasMany(MenuSectionBox::class, 'section_id')->orderBy('position');
    }
}
