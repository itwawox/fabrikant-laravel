<?php

namespace Database\Factories;

use App\Models\MenuSection;
use App\Models\MenuSectionBox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuSectionBox>
 */
class MenuSectionBoxFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => MenuSection::factory(),
            'x' => 0.1,
            'y' => 0.1,
            'w' => 0.4,
            'h' => 0.3,
            'position' => 0,
        ];
    }
}
