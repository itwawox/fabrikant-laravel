<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\MenuPage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuPage>
 */
class MenuPageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'number' => fake()->unique()->numberBetween(1, 1000),
            'title' => fake()->randomElement(['Пиво и закуски', 'Горячее и гриль', 'Десерты, чай, кофе']),
            'width' => 1600,
            'height' => 2263,
        ];
    }
}
