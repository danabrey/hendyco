<?php

namespace Database\Factories;

use App\Models\MileageLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MileageLog>
 */
class MileageLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'miles' => fake()->randomFloat(1, 2, 60),
            'purpose' => fake()->sentence(3),
        ];
    }
}
