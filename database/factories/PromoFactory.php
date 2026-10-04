<?php

namespace Database\Factories;

use App\Models\Promo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promo>
 */
class PromoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'title' => 'Счастливые часы',
            'discount' => '25%',
            'subject' => 'на меню кухни',
            'short' => '−25% на меню кухни',
            'rows' => [['label' => 'Когда', 'value' => 'с пн по пт']],
            'terms' => [],
            'is_active' => true,
            'days' => [1, 2, 3, 4, 5],
            'time_from' => '12:00',
            'time_to' => '15:00',
            'not_holidays' => true,
        ];
    }
}
