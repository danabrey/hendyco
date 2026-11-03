<?php

namespace App\Filament\Pages;

use App\Models\Expense;
use App\Reports\IncomeCalculator;
use App\Reports\MileageCostCalculator;
use App\Reports\UcAssessmentPeriod;
use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class UcReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'UC report';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.uc-report';

    protected static ?string $title = 'Universal Credit report';

    public string $from = '';

    public string $until = '';

    public function mount(): void
    {
        [$from, $until] = UcAssessmentPeriod::lastCompleted(today());

        $this->from = $from->toDateString();
        $this->until = $until->toDateString();
    }

    /**
     * @return array{from: CarbonImmutable, until: CarbonImmutable}|null
     */
    protected function range(): ?array
    {
        try {
            $from = CarbonImmutable::make($this->from ?: null);
            $until = CarbonImmutable::make($this->until ?: null);
        } catch (InvalidFormatException) {
            return null;
        }

        return $from && $until && $from->lte($until) ? ['from' => $from, 'until' => $until] : null;
    }

    /**
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
     * }|null
     */
    public function getReport(): ?array
    {
        $range = $this->range();

        if ($range === null) {
            return null;
        }

        $calculator = new IncomeCalculator;
        $income = $calculator->bookingIncome($range['from'], $range['until'])
            ->concat($calculator->giftCardSales($range['from'], $range['until']))
            ->sortBy(fn (array $entry): string => $entry['date']->toDateString())
            ->values();
        $expenses = Expense::query()
            ->with('category')
            ->whereBetween('date', [$range['from']->toDateString(), $range['until']->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();
        $mileage = MileageCostCalculator::uc()->entries($range['from'], $range['until']);

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
