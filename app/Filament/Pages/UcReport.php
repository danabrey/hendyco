<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasReportDateRange;
use App\NavigationGroup;
use App\Reports\UcAssessmentPeriod;
use App\Reports\UcReportBuilder;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class UcReport extends Page
{
    use HasReportDateRange;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'UC Report';

    protected string $view = 'filament.pages.uc-report';

    protected static ?string $title = 'Universal Credit report';

    public function mount(): void
    {
        [$from, $until] = UcAssessmentPeriod::lastCompleted(today());

        $this->from = $from->toDateString();
        $this->until = $until->toDateString();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getReport(): ?array
    {
        $range = $this->dateRange();

        return $range === null ? null : (new UcReportBuilder)->build($range['from'], $range['until']);
    }
}
