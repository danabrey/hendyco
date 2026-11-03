<?php

namespace App\GiftCards;

use App\GiftCardType;
use App\Models\BookingClient;
use App\Models\GiftCard;
use App\Models\GiftCardRedemption;
use App\PaymentMethod;

class GiftCardRedeemer
{
    /**
     * Make a client row's redemption match its gift card, price and booking.
     *
     * Any existing redemption is released first, so editing a row, deleting its booking
     * or changing the service always returns the card usage before it is re-applied.
     */
    public function sync(BookingClient $row): void
    {
        GiftCardRedemption::where('booking_client_id', $row->id)->delete();
        $row->unsetRelation('giftCardRedemption');

        $booking = $row->booking;

        if ($row->gift_card_id === null || $booking === null || $booking->trashed()) {
            return;
        }

        $card = GiftCard::find($row->gift_card_id);
        $covered = $card?->coverageFor($booking->service_id, $row->price_pence) ?? 0;

        if ($covered === 0) {
            $row->forceFill(['gift_card_id' => null])->saveQuietly();

            return;
        }

        $row->giftCardRedemption()->create([
            'gift_card_id' => $card->id,
            'service_id' => $card->type === GiftCardType::Service ? $booking->service_id : null,
            'amount_pence' => $covered,
        ]);

        if ($covered >= $row->price_pence) {
            $row->forceFill(['paid_at' => null, 'payment_method' => PaymentMethod::GiftCard])->saveQuietly();
        }
    }

    /**
     * Release every redemption held by the rows of a booking.
     */
    public function release(int $bookingId): void
    {
        GiftCardRedemption::whereIn('booking_client_id', BookingClient::where('booking_id', $bookingId)->select('id'))->delete();
    }

    /**
     * Re-apply the gift cards on every row of a booking.
     */
    public function syncBooking(int $bookingId): void
    {
        BookingClient::with('booking')->where('booking_id', $bookingId)->get()->each(fn (BookingClient $row) => $this->sync($row));
    }
}
