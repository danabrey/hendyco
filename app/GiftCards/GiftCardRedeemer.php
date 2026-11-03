<?php

namespace App\GiftCards;

use App\GiftCardType;
use App\Models\BookingClient;
use App\Models\GiftCard;
use App\Models\GiftCardRedemption;
use App\PaymentMethod;
use Illuminate\Support\Facades\DB;

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
        DB::transaction(function () use ($row): void {
            $existing = GiftCardRedemption::where('booking_client_id', $row->id)->first();
            $existing?->delete();
            $row->unsetRelation('giftCardRedemption');

            $booking = $row->booking;

            if ($row->gift_card_id === null || $booking === null || $booking->trashed()) {
                $this->clearStaleGiftCardMethod($row, $existing !== null);

                return;
            }

            // A card that has since expired or been deleted still keeps a redemption it already made.
            $keepsExisting = $existing !== null && $existing->gift_card_id === $row->gift_card_id;
            $card = GiftCard::withTrashed()->lockForUpdate()->find($row->gift_card_id);
            $covered = $card?->coverageFor($booking->service_id, $row->price_pence, null, $keepsExisting) ?? 0;

            if ($covered === 0) {
                $row->forceFill(['gift_card_id' => null])->saveQuietly();
                $this->clearStaleGiftCardMethod($row, true);

                return;
            }

            $row->giftCardRedemption()->create([
                'gift_card_id' => $card->id,
                'service_id' => $card->type === GiftCardType::Service ? $booking->service_id : null,
                'amount_pence' => $covered,
            ]);

            if ($covered >= $row->price_pence) {
                $row->forceFill(['paid_at' => null, 'payment_method' => PaymentMethod::GiftCard])->saveQuietly();
            } else {
                $this->clearStaleGiftCardMethod($row, true);
            }
        });
    }

    /**
     * A row that is no longer fully covered by a card must not stay marked as paid by gift card,
     * otherwise its remainder would be neither income nor unpaid.
     */
    protected function clearStaleGiftCardMethod(BookingClient $row, bool $hadGiftCard): void
    {
        if ($hadGiftCard && $row->payment_method === PaymentMethod::GiftCard) {
            $row->forceFill(['payment_method' => null, 'paid_at' => null])->saveQuietly();
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
        BookingClient::with('booking')->where('booking_id', $bookingId)->get()->each(function (BookingClient $row): void {
            $this->sync($row);
        });
    }
}
