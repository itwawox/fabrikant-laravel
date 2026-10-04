<?php

namespace Database\Factories;

use App\Enums\PhotoSize;
use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rubric_id' => GalleryRubric::factory(),
            'slug' => fake()->unique()->lexify('foto-????'),
            'size' => PhotoSize::Std,
            'alt' => 'Фасад ресторана вечером',
            'caption' => 'Вечерний фасад на Киевской.',
            'width' => 1024,
            'height' => 682,
            'variants' => [],
            'position' => 0,
        ];
    }
}
