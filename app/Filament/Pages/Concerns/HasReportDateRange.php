<?php

namespace App\Filament\Pages\Concerns;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

trait HasReportDateRange
{
    public string $from = '';

    public string $until = '';

    /**
     * The chosen range, or null when a date is missing, malformed or the start is after the end.
     *
     * @return array{from: CarbonImmutable, until: CarbonImmutable}|null
     */
    protected function dateRange(): ?array
    {
        try {
            $from = CarbonImmutable::make($this->from ?: null);
            $until = CarbonImmutable::make($this->until ?: null);
        } catch (InvalidFormatException) {
            return null;
        }

        return $from && $until && $from->lte($until) ? ['from' => $from, 'until' => $until] : null;
    }
}
