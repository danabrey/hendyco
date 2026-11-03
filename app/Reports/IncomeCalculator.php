<?php

namespace App\Reports;

use App\Models\BookingClient;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class IncomeCalculator
{
    /**
     * Cash received on bookings, by the date it was paid. Rows that count as £0
     * (for example those paid by gift card) are left out.
     *
     * @return Collection<int, array{date: CarbonInterface, label: string, amount_pence: int}>
     */
    public function bookingIncome(CarbonInterface $from, CarbonInterface $until): Collection
    {
        return BookingClient::query()
            ->with(['client' => fn ($query) => $query->withTrashed(), 'booking.service'])
            ->whereHas('booking')
            ->whereBetween('paid_at', [$from->toDateString(), $until->toDateString()])
            ->orderBy('paid_at')
            ->orderBy('id')
            ->get()
            ->map(fn (BookingClient $row): array => [
                'date' => $row->paid_at,
                'label' => $row->client->name.' – '.$row->booking->service->name,
                'amount_pence' => $row->incomePence(),
            ])
            ->filter(fn (array $entry): bool => $entry['amount_pence'] > 0)
            ->values();
    }
}
