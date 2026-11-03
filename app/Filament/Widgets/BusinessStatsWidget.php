<?php

namespace App\Filament\Widgets;

use App\Models\BookingClient;
use App\Models\Expense;
use App\Reports\IncomeCalculator;
use App\Reports\MileageCostCalculator;
use App\Reports\UcAssessmentPeriod;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BusinessStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'At a glance';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $today = CarbonImmutable::today();
        $monthStart = $today->startOfMonth();
        $lastMonthStart = $monthStart->subMonthNoOverflow();

        $incomeThisMonth = $this->income($monthStart, $today);
        $incomeLastMonth = $this->income($lastMonthStart, $monthStart->subDay());
        $expensesThisMonth = (int) Expense::query()
            ->whereBetween('date', [$monthStart->toDateString(), $today->toDateString()])
            ->sum('amount_pence');

        $unpaid = BookingClient::query()->awaitingPayment()->with('giftCardRedemption')->get();
        $owedPence = $unpaid->sum(fn (BookingClient $row): int => $row->outstandingPence());

        [$periodStart, $periodEnd] = UcAssessmentPeriod::containing($today);
        $periodMiles = MileageCostCalculator::uc()->entries($periodStart, $today)->sum('miles');
        $thresholdMiles = (int) config('hendyco.mileage.uc.threshold_miles');

        return [
            Stat::make('Income this month', $this->money($incomeThisMonth))
                ->description('Last month: '.$this->money($incomeLastMonth))
                ->color($incomeThisMonth >= $incomeLastMonth ? 'success' : 'gray'),
            Stat::make('Owed to you', $this->money($owedPence))
                ->description($unpaid->count().' unpaid '.str('booking')->plural($unpaid->count()))
                ->color($owedPence > 0 ? 'warning' : 'success'),
            Stat::make('Expenses this month', $this->money($expensesThisMonth))
                ->description('Net this month: '.$this->money($incomeThisMonth - $expensesThisMonth)),
            Stat::make('UC period mileage', rtrim(rtrim(number_format($periodMiles, 1), '0'), '.').' miles')
                ->description($this->mileageDescription($periodMiles, $thresholdMiles, $periodEnd)),
        ];
    }

    protected function income(CarbonImmutable $from, CarbonImmutable $until): int
    {
        $calculator = new IncomeCalculator;

        return $calculator->bookingIncome($from, $until)->sum('amount_pence')
            + $calculator->giftCardSales($from, $until)->sum('amount_pence');
    }

    protected function mileageDescription(float $miles, int $threshold, CarbonImmutable $periodEnd): string
    {
        $left = max(0, $threshold - $miles);
        $rate = (int) config('hendyco.mileage.uc.rate_pence');

        return rtrim(rtrim(number_format($left, 1), '0'), '.')." left at {$rate}p · period ends ".$periodEnd->format('j M');
    }

    protected function money(int $pence): string
    {
        return '£'.number_format($pence / 100, 2);
    }
}
