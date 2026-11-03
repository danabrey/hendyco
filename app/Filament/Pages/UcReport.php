<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasReportDateRange;
use App\Reports\UcAssessmentPeriod;
use App\Reports\UcReportBuilder;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class UcReport extends Page
{
    use HasReportDateRange;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'UC report';

    protected static ?int $navigationSort = 2;

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
