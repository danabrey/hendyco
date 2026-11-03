<?php

namespace App\Reports;

use App\Models\Expense;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class UcReportBuilder
{
    /**
     * Income received, expenses and mileage at the UC rate between two dates, with totals.
     *
     * @return array{
     *     income: Collection<int, array<string, mixed>>,
     *     expenses: Collection<int, Expense>,
     *     mileage: Collection<int, array<string, mixed>>,
     *     income_pence: int,
     *     expenses_pence: int,
     *     miles: float,
     *     mileage_pence: int,
     *     expenditure_pence: int,
     *     net_pence: int,
     * }
     */
    public function build(CarbonInterface $from, CarbonInterface $until): array
    {
        $calculator = new IncomeCalculator;
        $income = $calculator->bookingIncome($from, $until)
            ->concat($calculator->giftCardSales($from, $until))
            ->sortBy(fn (array $entry): string => $entry['date']->toDateString())
            ->values();
        $expenses = Expense::query()
            ->with('category')
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();
        $mileage = MileageCostCalculator::uc()->entries($from, $until);

        $incomePence = $income->sum('amount_pence');
        $expensesPence = $expenses->sum('amount_pence');
        $mileagePence = $mileage->sum('cost_pence');

        return [
            'income' => $income,
            'expenses' => $expenses,
            'mileage' => $mileage,
            'income_pence' => $incomePence,
            'expenses_pence' => $expensesPence,
            'miles' => $mileage->sum('miles'),
            'mileage_pence' => $mileagePence,
            'expenditure_pence' => $expensesPence + $mileagePence,
            'net_pence' => $incomePence - $expensesPence - $mileagePence,
        ];
    }
}
