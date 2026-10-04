<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\MenuSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuSection>
 */
class MenuSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'page_number' => 1,
            'title' => 'Супы',
            'slug' => 'supy-'.fake()->unique()->numberBetween(1, 100000),
            'slug_locked' => false,
            'position' => 0,
        ];
    }
}
