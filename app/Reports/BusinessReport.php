<?php

namespace App\Reports;

use App\Models\Expense;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BusinessReport
{
    public function __construct(
        protected IncomeCalculator $income = new IncomeCalculator,
        protected ?MileageCostCalculator $mileage = null,
    ) {
        $this->mileage ??= MileageCostCalculator::hmrc();
    }

    /**
     * Income, expenses, mileage and net profit between two dates, broken down for reading.
     *
     * @return array{
     *     income: array{total: int, by_month: list<array{label: string, amount_pence: int}>, by_service: list<array{label: string, amount_pence: int}>, by_client: list<array{label: string, amount_pence: int}>},
     *     expenses: array{total: int, by_month: list<array{label: string, amount_pence: int}>, by_category: list<array{label: string, amount_pence: int}>},
     *     mileage: array{miles: float, cost_pence: int, by_tax_year: list<array{label: string, miles: float, cost_pence: int}>},
     *     net: array{total: int, by_tax_year: list<array{label: string, income_pence: int, expenses_pence: int, mileage_pence: int, net_pence: int}>},
     * }
     */
    public function build(CarbonInterface $from, CarbonInterface $until): array
    {
        $from = CarbonImmutable::instance($from)->startOfDay();
        $until = CarbonImmutable::instance($until)->startOfDay();

        $income = $this->income->bookingIncome($from, $until)->concat($this->income->giftCardSales($from, $until));
        $expenses = Expense::query()
            ->with('category')
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->get();
        $mileage = $this->mileage->entries($from, $until);

        $incomeTotal = $income->sum('amount_pence');
        $expensesTotal = $expenses->sum('amount_pence');
        $mileageTotal = $mileage->sum('cost_pence');

        return [
            'income' => [
                'total' => $incomeTotal,
                'by_month' => $this->totals($income, fn (array $entry): string => $entry['date']->format('Y-m'), fn (array $entry): string => $entry['date']->format('F Y')),
                'by_service' => $this->totals($income, fn (array $entry): string => $entry['service'], fn (array $entry): string => $entry['service'], byAmount: true),
                'by_client' => $this->totals($income, fn (array $entry): string => $entry['client'], fn (array $entry): string => $entry['client'], byAmount: true),
            ],
            'expenses' => [
                'total' => $expensesTotal,
                'by_month' => $this->totals($expenses, fn (Expense $expense): string => $expense->date->format('Y-m'), fn (Expense $expense): string => $expense->date->format('F Y')),
                'by_category' => $this->totals($expenses, fn (Expense $expense): string => $expense->category->name, fn (Expense $expense): string => $expense->category->name, byAmount: true),
            ],
            'mileage' => [
                'miles' => $mileage->sum('miles'),
                'cost_pence' => $mileageTotal,
                'by_tax_year' => $this->mileageByTaxYear($mileage),
            ],
            'net' => [
                'total' => $incomeTotal - $expensesTotal - $mileageTotal,
                'by_tax_year' => $this->netByTaxYear($income, $expenses, $mileage),
            ],
        ];
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return list<array{label: string, amount_pence: int}>
     */
    protected function totals(Collection $items, callable $key, callable $label, bool $byAmount = false): array
    {
        $groups = $items
            ->groupBy($key)
            ->map(fn (Collection $group): array => [
                'label' => $label($group->first()),
                'amount_pence' => (int) $group->sum('amount_pence'),
            ]);

        return ($byAmount ? $groups->sortByDesc('amount_pence') : $groups->sortKeys())->values()->all();
    }

    /**
     * @param  Collection<int, array{date: CarbonImmutable, miles: float, cost_pence: int}>  $mileage
     * @return list<array{label: string, miles: float, cost_pence: int}>
     */
    protected function mileageByTaxYear(Collection $mileage): array
    {
        return $mileage
            ->groupBy(fn (array $entry): string => TaxYear::labelFor($entry['date']))
            ->map(fn (Collection $group, string $label): array => [
                'label' => $label,
                'miles' => $group->sum('miles'),
                'cost_pence' => $group->sum('cost_pence'),
            ])
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $income
     * @param  Collection<int, Expense>  $expenses
     * @param  Collection<int, array<string, mixed>>  $mileage
     * @return list<array{label: string, income_pence: int, expenses_pence: int, mileage_pence: int, net_pence: int}>
     */
    protected function netByTaxYear(Collection $income, Collection $expenses, Collection $mileage): array
    {
        $incomeByYear = $income->groupBy(fn (array $entry): string => TaxYear::labelFor($entry['date']))->map->sum('amount_pence');
        $expensesByYear = $expenses->groupBy(fn (Expense $expense): string => TaxYear::labelFor($expense->date))->map->sum('amount_pence');
        $mileageByYear = $mileage->groupBy(fn (array $entry): string => TaxYear::labelFor($entry['date']))->map->sum('cost_pence');

        return $incomeByYear->keys()->merge($expensesByYear->keys())->merge($mileageByYear->keys())
            ->unique()
            ->sort()
            ->map(fn (string $label): array => [
                'label' => $label,
                'income_pence' => (int) ($incomeByYear[$label] ?? 0),
                'expenses_pence' => (int) ($expensesByYear[$label] ?? 0),
                'mileage_pence' => (int) ($mileageByYear[$label] ?? 0),
                'net_pence' => (int) (($incomeByYear[$label] ?? 0) - ($expensesByYear[$label] ?? 0) - ($mileageByYear[$label] ?? 0)),
            ])
            ->values()
            ->all();
    }
}
