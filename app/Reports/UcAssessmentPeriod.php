<?php

namespace App\Reports;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class UcAssessmentPeriod
{
    /**
     * The 16th-to-15th period that contains the given date.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function containing(CarbonInterface $date): array
    {
        $date = CarbonImmutable::instance($date)->startOfDay();
        $start = $date->day >= 16 ? $date->day(16) : $date->subMonthNoOverflow()->day(16);

        return [$start, $start->addMonthNoOverflow()->day(15)];
    }

    /**
     * The most recent 16th-to-15th period that has finished before the given date.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function lastCompleted(CarbonInterface $today): array
    {
        [$currentStart] = static::containing($today);
        $end = $currentStart->subDay();

        return [$end->subMonthNoOverflow()->day(16), $end];
    }
}
