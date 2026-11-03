<?php

namespace Database\Factories;

use App\Models\GiftCard;
use App\Models\GiftCardLine;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCardLine>
 */
class GiftCardLineFactory extends Factory
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
            'service_id' => Service::factory(),
            'quantity' => 1,
        ];
    }
}
