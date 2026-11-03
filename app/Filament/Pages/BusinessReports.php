<?php

namespace App\Filament\Pages;

use App\Reports\BusinessReport;
use App\Reports\TaxYear;
use BackedEnum;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BusinessReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Business reports';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.business-reports';

    protected static ?string $title = 'Business reports';

    public string $from = '';

    public string $until = '';

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
        try {
            $from = CarbonImmutable::make($this->from ?: null);
            $until = CarbonImmutable::make($this->until ?: null);
        } catch (InvalidFormatException) {
            return null;
        }

        if (! $from || ! $until || $from->gt($until)) {
            return null;
        }

        return (new BusinessReport)->build($from, $until);
    }
}
