<?php

namespace App\Reports;

use App\Models\MileageLog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MileageCostCalculator
{
    /**
     * @param  array{period: string, threshold_miles: int, rate_pence: int, rate_pence_after: int}  $rates
     */
    public function __construct(protected array $rates) {}

    public static function uc(): static
    {
        return new static(config('hendyco.mileage.uc'));
    }

    public static function hmrc(): static
    {
        return new static(config('hendyco.mileage.hmrc'));
    }

    /**
     * Costed mileage entries dated within the range. Miles already driven earlier
     * in the same period count towards the threshold, even before the range starts.
     *
     * @return Collection<int, array{date: CarbonImmutable, label: string, miles: float, cost_pence: int}>
     */
    public function entries(CarbonInterface $from, CarbonInterface $until): Collection
    {
        $from = CarbonImmutable::instance($from)->startOfDay();
        $until = CarbonImmutable::instance($until)->startOfDay();
        $periodStart = $this->periodStart($from);

        $driven = [];

        return $this->rawEntries($periodStart, $until)
            ->map(function (array $entry) use (&$driven): array {
                $key = $this->periodStart($entry['date'])->toDateString();
                $before = $driven[$key] ?? 0.0;
                $driven[$key] = $before + $entry['miles'];

                return [...$entry, 'cost_pence' => $this->costOf($before, $entry['miles'])];
            })
            ->filter(fn (array $entry): bool => $entry['date']->betweenIncluded($from, $until))
            ->values();
    }

    /**
     * @return Collection<int, array{date: CarbonImmutable, label: string, miles: float, created_at: CarbonInterface}>
     */
    protected function rawEntries(CarbonImmutable $from, CarbonImmutable $until): Collection
    {
        $logs = MileageLog::query()
            ->where('miles', '>', 0)
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->get()
            ->map(fn (MileageLog $log): array => [
                'date' => CarbonImmutable::instance($log->date),
                'label' => $log->purpose,
                'miles' => (float) $log->miles,
                'created_at' => $log->created_at,
                'id' => $log->id,
            ]);

        return $logs
            ->sortBy([
                fn (array $a, array $b): int => $a['date'] <=> $b['date'],
                fn (array $a, array $b): int => $a['created_at'] <=> $b['created_at'],
                fn (array $a, array $b): int => $a['id'] <=> $b['id'],
            ])
            ->values()
            ->map(fn (array $entry): array => collect($entry)->only(['date', 'label', 'miles', 'created_at'])->all());
    }

    protected function costOf(float $milesBefore, float $miles): int
    {
        $threshold = $this->rates['threshold_miles'];
        $atFirstRate = max(0.0, min($miles, $threshold - $milesBefore));
        $atSecondRate = $miles - $atFirstRate;

        return (int) round($atFirstRate * $this->rates['rate_pence'] + $atSecondRate * $this->rates['rate_pence_after']);
    }

    protected function periodStart(CarbonInterface $date): CarbonImmutable
    {
        if ($this->rates['period'] === 'tax_year') {
            $date = CarbonImmutable::instance($date)->startOfDay();
            $start = $date->setDate($date->year, 4, 6);

            return $date->lt($start) ? $start->subYear() : $start;
        }

        return UcAssessmentPeriod::containing($date)[0];
    }
}
