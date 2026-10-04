<?php

namespace App\Models;

use App\Enums\MenuStatus;
use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'season', 'name', 'status', 'source_pdf_path', 'web_pdf_path', 'pages_count', 'sheet_w_pt', 'sheet_h_pt',
    'storage_dir', 'published_at', 'scheduled_at', 'processed_at', 'error', 'created_by',
])]
class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MenuStatus::class,
            'pages_count' => 'integer',
            'sheet_w_pt' => 'float',
            'sheet_h_pt' => 'float',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /** @return HasMany<MenuPage, $this> */
    public function pages(): HasMany
    {
        return $this->hasMany(MenuPage::class)->orderBy('number');
    }

    /** @return HasMany<MenuSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(MenuSection::class)->orderBy('position');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Опубликованное меню. Оно всегда одно — это обеспечивает публикация (этап 3).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', MenuStatus::Published);
    }
}
