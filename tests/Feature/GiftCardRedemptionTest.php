<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingClient;
use App\Models\GiftCard;
use App\Models\GiftCardLine;
use App\Models\Service;
use App\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GiftCardRedemptionTest extends TestCase
{
    use RefreshDatabase;

    protected function rowFor(Booking $booking, GiftCard $card, int $price = 1500, array $state = []): BookingClient
    {
        return BookingClient::factory()->for($booking)->create([...['price_pence' => $price, 'gift_card_id' => $card->id], ...$state]);
    }

    public function test_codes_are_unique_and_human_friendly(): void
    {
        $codes = GiftCard::factory(20)->create()->pluck('code');

        $this->assertCount(20, $codes->unique());
        $codes->each(fn (string $code) => $this->assertMatchesRegularExpression('/^HENDY-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $code));
    }

    public function test_a_money_card_covering_the_whole_price_marks_the_row_paid_by_gift_card(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 5000]);
        $row = $this->rowFor(Booking::factory()->create(), $card, 1500, ['paid_at' => '2026-10-01', 'payment_method' => PaymentMethod::Cash]);

        $row->refresh();
        $this->assertSame(3500, $card->balancePence());
        $this->assertNull($row->paid_at);
        $this->assertSame(PaymentMethod::GiftCard, $row->payment_method);
        $this->assertSame(0, $row->incomePence());
        $this->assertFalse(BookingClient::unpaid()->whereKey($row->id)->exists());
    }

    public function test_a_money_card_covers_only_its_balance_and_the_remainder_is_paid_another_way(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 1000]);
        $row = $this->rowFor(Booking::factory()->create(), $card, 1500);

        $this->assertSame(0, $card->balancePence());
        $this->assertTrue(BookingClient::unpaid()->whereKey($row->id)->exists());

        $row->update(['paid_at' => '2026-10-02', 'payment_method' => PaymentMethod::Cash]);

        $row->refresh();
        $this->assertSame(PaymentMethod::Cash, $row->payment_method);
        $this->assertSame('2026-10-02', $row->paid_at->toDateString());
        $this->assertSame(500, $row->incomePence());
        $this->assertFalse(BookingClient::unpaid()->whereKey($row->id)->exists());
    }

    public function test_a_service_card_covers_an_included_service_and_uses_one_unit(): void
    {
        $service = Service::factory()->create();
        $card = GiftCard::factory()->service()->create();
        $card->lines()->delete();
        GiftCardLine::factory()->for($card)->create(['service_id' => $service->id, 'quantity' => 2]);

        $first = $this->rowFor(Booking::factory()->create(['service_id' => $service->id]), $card);
        $second = $this->rowFor(Booking::factory()->create(['service_id' => $service->id]), $card);
        $third = $this->rowFor(Booking::factory()->create(['service_id' => $service->id]), $card);

        $this->assertSame(0, $card->remainingQuantityFor($service->id));
        $this->assertNotNull($first->fresh()->giftCardRedemption);
        $this->assertNotNull($second->fresh()->giftCardRedemption);
        $this->assertNull($third->fresh()->gift_card_id, 'no quantity left');
        $this->assertNull($third->fresh()->giftCardRedemption);
    }

    public function test_a_service_card_is_not_redeemable_for_a_service_it_does_not_include(): void
    {
        $card = GiftCard::factory()->service()->create();

        $row = $this->rowFor(Booking::factory()->create(), $card);

        $this->assertNull($row->fresh()->gift_card_id);
        $this->assertDatabaseCount('gift_card_redemptions', 0);
    }

    public function test_expired_and_deleted_cards_cannot_be_redeemed(): void
    {
        $expired = GiftCard::factory()->create(['expires_at' => today()->subDay()]);
        $deleted = GiftCard::factory()->create();
        $deleted->delete();

        $this->rowFor(Booking::factory()->create(), $expired);
        $this->rowFor(Booking::factory()->create(), $deleted);

        $this->assertDatabaseCount('gift_card_redemptions', 0);
        $this->assertSame(5000, $expired->balancePence());
    }

    public function test_deleting_a_booking_returns_the_usage_and_restoring_it_applies_it_again(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 5000]);
        $booking = Booking::factory()->create();
        $this->rowFor($booking, $card, 1500);

        $booking->delete();
        $this->assertSame(5000, $card->balancePence());

        $booking->restore();
        $this->assertSame(3500, $card->balancePence());
    }

    public function test_removing_the_card_or_deleting_the_row_returns_the_usage(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 5000]);
        $row = $this->rowFor(Booking::factory()->create(), $card, 1500);
        $other = $this->rowFor(Booking::factory()->create(), $card, 1000);
        $this->assertSame(2500, $card->balancePence());

        $row->update(['gift_card_id' => null]);
        $this->assertSame(4000, $card->balancePence());

        $other->delete();
        $this->assertSame(5000, $card->balancePence());
    }

    public function test_editing_the_price_recalculates_the_coverage(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 5000]);
        $row = $this->rowFor(Booking::factory()->create(), $card, 1500);

        $row->update(['price_pence' => 2000]);

        $this->assertSame(3000, $card->balancePence());
        $this->assertSame(1, $card->redemptions()->count());
    }

    public function test_changing_a_bookings_service_rechecks_a_service_card(): void
    {
        $included = Service::factory()->create();
        $card = GiftCard::factory()->service()->create();
        $card->lines()->delete();
        GiftCardLine::factory()->for($card)->create(['service_id' => $included->id, 'quantity' => 1]);
        $booking = Booking::factory()->create(['service_id' => $included->id]);
        $row = $this->rowFor($booking, $card);
        $this->assertSame(0, $card->remainingQuantityFor($included->id));

        $booking->update(['service_id' => Service::factory()->create()->id]);

        $this->assertSame(1, $card->remainingQuantityFor($included->id));
        $this->assertNull($row->fresh()->gift_card_id);
    }

    public function test_a_redemption_survives_the_card_expiring_or_being_deleted(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 5000, 'expires_at' => today()->addDay()]);
        $row = $this->rowFor(Booking::factory()->create(), $card, 1500);

        $card->update(['expires_at' => today()->subDay()]);
        $row->update(['price_pence' => 1500]);
        $this->assertSame(3500, $card->balancePence());

        $card->delete();
        $row->refresh()->update(['price_pence' => 1500]);
        $this->assertSame(1, $card->redemptions()->count());
        $this->assertSame($card->id, $row->fresh()->gift_card_id);
    }

    public function test_raising_the_price_after_full_cover_leaves_the_remainder_unpaid(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 1500]);
        $row = $this->rowFor(Booking::factory()->create(), $card, 1500);
        $this->assertSame(PaymentMethod::GiftCard, $row->fresh()->payment_method);

        $row->update(['price_pence' => 2000]);

        $row->refresh();
        $this->assertNull($row->payment_method);
        $this->assertSame(1500, (int) $card->redemptions()->sum('amount_pence'));
        $this->assertTrue(BookingClient::unpaid()->whereKey($row->id)->exists());
    }

    public function test_removing_the_card_clears_the_gift_card_payment_method(): void
    {
        $card = GiftCard::factory()->create(['value_pence' => 5000]);
        $row = $this->rowFor(Booking::factory()->create(), $card, 1500);

        $row->update(['gift_card_id' => null]);

        $this->assertNull($row->fresh()->payment_method);
        $this->assertTrue(BookingClient::unpaid()->whereKey($row->id)->exists());
    }

    public function test_status_and_scopes_tell_used_available_and_expired_cards_apart(): void
    {
        $service = Service::factory()->create();
        $serviceCard = function (int $quantity, int $uses) use ($service): GiftCard {
            $card = GiftCard::factory()->service()->create();
            $card->lines()->delete();
            GiftCardLine::factory()->for($card)->create(['service_id' => $service->id, 'quantity' => $quantity]);
            foreach (range(1, $uses) as $ignored) {
                if ($uses > 0) {
                    $this->rowFor(Booking::factory()->create(['service_id' => $service->id]), $card);
                }
            }

            return $card;
        };

        $moneyUsed = GiftCard::factory()->create(['value_pence' => 1500]);
        $this->rowFor(Booking::factory()->create(), $moneyUsed, 1500);
        $moneyPartly = GiftCard::factory()->create(['value_pence' => 5000]);
        $this->rowFor(Booking::factory()->create(), $moneyPartly, 1500);
        $moneyUntouched = GiftCard::factory()->create();
        $serviceUsed = $serviceCard(1, 1);
        $servicePartly = $serviceCard(2, 1);
        $expiredUnused = GiftCard::factory()->create(['expires_at' => today()->subDay()]);
        $expiredButUsed = GiftCard::factory()->create(['value_pence' => 1500, 'expires_at' => today()->addDay()]);
        $this->rowFor(Booking::factory()->create(), $expiredButUsed, 1500);
        $expiredButUsed->update(['expires_at' => today()->subDay()]);

        $this->assertSame([true, false, false, true, false, false, true], array_map(fn (GiftCard $card) => $card->isUsed(), [$moneyUsed, $moneyPartly, $moneyUntouched, $serviceUsed, $servicePartly, $expiredUnused, $expiredButUsed]));

        $ids = fn ($query) => $query->pluck('id')->sort()->values()->all();
        $this->assertSame($ids(collect([$moneyUsed, $serviceUsed, $expiredButUsed])), $ids(GiftCard::used()));
        $this->assertSame($ids(collect([$moneyPartly, $moneyUntouched, $servicePartly])), $ids(GiftCard::available()));
        $this->assertSame($ids(collect([$expiredUnused])), $ids(GiftCard::expired()));
        $this->assertSame('Expired', $expiredUnused->status()->getLabel());
        $this->assertSame('Used', $expiredButUsed->status()->getLabel());
    }
}
