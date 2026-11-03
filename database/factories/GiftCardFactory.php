<?php

namespace Database\Factories;

use App\GiftCardType;
use App\Models\GiftCard;
use App\Models\GiftCardLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftCard>
 */
class GiftCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => GiftCardType::Money,
            'value_pence' => 5000,
            'price_paid_pence' => 5000,
            'sold_at' => today()->toDateString(),
            'purchaser_name' => fake()->name(),
            'recipient_name' => fake()->name(),
        ];
    }

    public function service(): static
    {
        return $this->state(['type' => GiftCardType::Service, 'value_pence' => null])
            ->afterCreating(function (GiftCard $card): void {
                if ($card->lines()->doesntExist()) {
                    GiftCardLine::factory()->for($card)->create();
                }
            });
    }
}
