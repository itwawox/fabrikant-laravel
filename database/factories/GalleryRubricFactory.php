<?php

namespace Database\Factories;

use App\Models\GalleryRubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryRubric>
 */
class GalleryRubricFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->lexify('rubric-????'),
            'title' => 'Ресторан',
            'lede' => 'Кирпичные стены, латунные светильники.',
            'position' => 0,
        ];
    }
}
