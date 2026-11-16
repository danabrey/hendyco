<?php

namespace Tests\Unit;

use App\Filament\Resources\Bookings\Schemas\ClientRowPrices;
use App\Filament\Resources\Bookings\Schemas\SuggestedPrices;
use App\Models\Service;
use PHPUnit\Framework\TestCase;

class ClientRowPricesTest extends TestCase
{
    public function test_a_new_row_without_a_price_gets_its_clients_suggested_price(): void
    {
        $result = ClientRowPrices::refresh(
            rows: ['a' => ['client_id' => 7, 'price_pence' => null]],
            lastSuggested: [],
            prices: $this->walkPrices([7 => 2]),
        );

        $this->assertSame(19.5, $result['rows']['a']['price_pence']);
        $this->assertSame(['a' => 1950], $result['suggested']);
    }

    public function test_a_price_typed_in_by_hand_is_left_alone(): void
    {
        $result = ClientRowPrices::refresh(
            rows: ['a' => ['client_id' => 7, 'price_pence' => 10]],
            lastSuggested: ['a' => 1650],
            prices: $this->walkPrices([7 => 2]),
        );

        $this->assertSame(10, $result['rows']['a']['price_pence']);
    }

    public function test_a_row_still_at_its_last_suggested_price_moves_to_the_new_suggestion(): void
    {
        $result = ClientRowPrices::refresh(
            rows: ['a' => ['client_id' => 7, 'price_pence' => 16.5]],
            lastSuggested: ['a' => 1650],
            prices: $this->walkPrices([7 => 3]),
        );

        $this->assertSame(22.5, $result['rows']['a']['price_pence']);
        $this->assertSame(['a' => 2250], $result['suggested']);
    }

    public function test_a_row_with_a_price_but_nothing_suggested_yet_is_left_alone(): void
    {
        $result = ClientRowPrices::refresh(
            rows: ['a' => ['client_id' => 7, 'price_pence' => 16.5]],
            lastSuggested: [],
            prices: $this->walkPrices([7 => 2]),
        );

        $this->assertSame(16.5, $result['rows']['a']['price_pence']);
        $this->assertSame([], $result['suggested']);
    }

    public function test_suggestions_for_removed_rows_are_dropped(): void
    {
        $result = ClientRowPrices::refresh(
            rows: [],
            lastSuggested: ['gone' => 1650],
            prices: $this->walkPrices([]),
        );

        $this->assertSame([], $result['suggested']);
    }

    public function test_saved_rows_start_with_their_clients_current_suggested_price(): void
    {
        $suggested = ClientRowPrices::suggestionsFor(
            rows: ['record-1' => ['client_id' => 7, 'price_pence' => 16.5], 'record-2' => ['client_id' => 8, 'price_pence' => 12]],
            prices: $this->walkPrices([7 => 2, 8 => 1]),
        );

        $this->assertSame(['record-1' => 1950, 'record-2' => 1650], $suggested);
    }

    /**
     * Suggested prices for a £16.50 walk with £3 per extra dog.
     *
     * @param  array<int, int>  $dogCountsByClientId
     */
    private function walkPrices(array $dogCountsByClientId): SuggestedPrices
    {
        return new SuggestedPrices(new Service(['default_price_pence' => 1650, 'extra_dog_price_pence' => 300]), $dogCountsByClientId);
    }
}
