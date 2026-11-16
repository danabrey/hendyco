<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Forms\PenceInput;

/**
 * Keeps the booking form's client row prices in step with their suggested prices.
 */
class ClientRowPrices
{
    /**
     * Give each row its client's suggested price, unless its price has been typed in by hand.
     *
     * @param  array<string, array<string, mixed>>  $rows  The repeater rows, with prices in pounds.
     * @param  array<string, int>  $lastSuggested  The pence last suggested for each row, by row key.
     * @return array{rows: array<string, array<string, mixed>>, suggested: array<string, int>}
     */
    public static function refresh(array $rows, array $lastSuggested, SuggestedPrices $prices): array
    {
        $suggested = [];

        foreach ($rows as $key => $row) {
            $price = $row['price_pence'] ?? null;

            if (! static::followsSuggestion($price, $lastSuggested[$key] ?? null)) {
                if (isset($lastSuggested[$key])) {
                    $suggested[$key] = $lastSuggested[$key];
                }

                continue;
            }

            $suggestedPence = $prices->forClient($row['client_id'] ?? null);
            $rows[$key]['price_pence'] = PenceInput::toPounds($suggestedPence);
            $suggested[$key] = $suggestedPence;
        }

        return ['rows' => $rows, 'suggested' => $suggested];
    }

    /**
     * The price currently suggested for each row, by row key, used as the starting point for saved rows.
     *
     * @param  array<string, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    public static function suggestionsFor(array $rows, SuggestedPrices $prices): array
    {
        return array_map(fn (array $row): int => $prices->forClient($row['client_id'] ?? null), $rows);
    }

    /**
     * Whether a row's price should track its suggestion: it has no price yet, or still has the one last suggested.
     */
    protected static function followsSuggestion(mixed $pounds, ?int $lastSuggestedPence): bool
    {
        if (blank($pounds)) {
            return true;
        }

        return $lastSuggestedPence !== null && is_numeric($pounds) && PenceInput::toPence($pounds) === $lastSuggestedPence;
    }
}
