<?php

namespace App\Models;

use Database\Factories\GalleryRubricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'title', 'lede', 'position'])]
class GalleryRubric extends Model
{
    /** @use HasFactory<GalleryRubricFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /** @return HasMany<GalleryPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class, 'rubric_id')->orderBy('position');
    }
}
