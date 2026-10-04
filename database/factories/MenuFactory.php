<?php

namespace Database\Factories;

use App\Enums\MenuStatus;
use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'season' => 'Осень 2026',
            'name' => 'menu_'.fake()->unique()->bothify('????_####'),
            'status' => MenuStatus::Draft,
            'pages_count' => 2,
            'sheet_w_pt' => 841.89,
            'sheet_h_pt' => 1190.55,
        ];
    }
}
