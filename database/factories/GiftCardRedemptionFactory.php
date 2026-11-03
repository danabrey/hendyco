<?php

namespace Database\Factories;

use App\Models\BookingClient;
use App\Models\GiftCard;
use App\Models\GiftCardRedemption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCardRedemption>
 */
class GiftCardRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gift_card_id' => GiftCard::factory(),
            'booking_client_id' => BookingClient::factory(),
            'amount_pence' => 1000,
        ];
    }
}
