<?php

namespace App\Reports;

use App\Models\BookingClient;
use App\Models\GiftCard;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class IncomeCalculator
{
    public const GIFT_CARD_SALES_LABEL = 'Gift card sales';

    /**
     * Gift card sales, counted as income on the sale date.
     *
     * @return Collection<int, array{date: CarbonInterface, label: string, service: string, client: string, amount_pence: int}>
     */
    public function giftCardSales(CarbonInterface $from, CarbonInterface $until): Collection
    {
        return GiftCard::query()
            ->whereBetween('sold_at', [$from->toDateString(), $until->toDateString()])
            ->where('price_paid_pence', '>', 0)
            ->orderBy('sold_at')
            ->orderBy('id')
            ->get()
            ->map(fn (GiftCard $card): array => [
                'date' => $card->sold_at,
                'label' => 'Gift card sale – '.$card->code,
                'service' => self::GIFT_CARD_SALES_LABEL,
                'client' => self::GIFT_CARD_SALES_LABEL,
                'amount_pence' => $card->price_paid_pence,
            ]);
    }

    /**
     * Cash received on bookings, by the date it was paid. Rows that count as £0
     * (for example those paid by gift card) are left out.
     *
     * @return Collection<int, array{date: CarbonInterface, label: string, service: string, client: string, amount_pence: int}>
     */
    public function bookingIncome(CarbonInterface $from, CarbonInterface $until): Collection
    {
        return BookingClient::query()
            ->with(['client' => fn ($query) => $query->withTrashed(), 'booking.service', 'giftCardRedemption'])
            ->whereHas('booking')
            ->whereBetween('paid_at', [$from->toDateString(), $until->toDateString()])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->map(fn (BookingClient $row): array => [
                'date' => $row->paid_at,
                'label' => $row->client->name.' – '.$row->booking->service->name,
                'service' => $row->booking->service->name,
                'client' => $row->client->name,
                'amount_pence' => $row->incomePence(),
            ])
            ->filter(fn (array $entry): bool => $entry['amount_pence'] > 0)
            ->values();
    }
}
