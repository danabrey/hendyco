<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasReportDateRange;
use App\Reports\BusinessReport;
use App\Reports\TaxYear;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BusinessReports extends Page
{
    use HasReportDateRange;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Business reports';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.business-reports';

    protected static ?string $title = 'Business reports';

    public function mount(): void
    {
        $this->from = TaxYear::startOf(today())->toDateString();
        $this->until = today()->toDateString();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getReport(): ?array
    {
        $range = $this->dateRange();

        if ($range === null) {
            return null;
        }

        return (new BusinessReport)->build($range['from'], $range['until']);
    }
}
