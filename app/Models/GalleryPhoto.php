<?php

namespace App\Models;

use App\Enums\PhotoSize;
use App\Models\Concerns\LogsChanges;
use Database\Factories\GalleryPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['rubric_id', 'slug', 'size', 'alt', 'caption', 'source_path', 'width', 'height', 'variants', 'position'])]
class GalleryPhoto extends Model
{
    use LogsChanges;

    /** @var list<string> служебные поля — в журнал не пишем */
    protected array $logIgnore = ['variants', 'width', 'height', 'source_path'];

    /** @use HasFactory<GalleryPhotoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'size' => PhotoSize::class,
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<GalleryRubric, $this> */
    public function rubric(): BelongsTo
    {
        return $this->belongsTo(GalleryRubric::class, 'rubric_id');
    }
}
