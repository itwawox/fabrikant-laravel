<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->addDays(2)->toDateString(),
            'time' => '19:30',
            'guests' => 4,
            'name' => 'Анна',
            'phone' => '+7 978 123 45 67',
            'comment' => null,
            'status' => BookingStatus::New,
            'source' => 'site',
            'consent_at' => now(),
        ];
    }
}
