<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\Client;
use App\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingClient>
 */
class BookingClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'client_id' => Client::factory(),
            'price_pence' => 1500,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'paid_at' => now()->toDateString(),
            'payment_method' => PaymentMethod::Cash,
        ]);
    }
}
