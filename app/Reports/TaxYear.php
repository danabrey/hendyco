<?php

namespace App\Reports;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class TaxYear
{
    /**
     * The first day (6 April) of the tax year that contains the date.
     */
    public static function startOf(CarbonInterface $date): CarbonImmutable
    {
        $date = CarbonImmutable::instance($date)->startOfDay();
        $start = $date->setDate($date->year, 4, 6);

        return $date->lt($start) ? $start->subYear() : $start;
    }

    /**
     * A label such as 2026/27 for the tax year that contains the date.
     */
    public static function labelFor(CarbonInterface $date): string
    {
        $start = static::startOf($date);

        return $start->year.'/'.$start->addYear()->format('y');
    }
}
