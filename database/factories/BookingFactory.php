<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'start_time' => fake()->randomElement(['09:00', '11:30', '14:00']),
            'duration_minutes' => 60,
            'service_id' => Service::factory(),
            'miles' => fake()->randomFloat(2, 0, 20),
        ];
    }
}
