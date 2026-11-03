<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingsCalendarWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BookingsCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.bookings-calendar';

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [BookingsCalendarWidget::class];
    }
}
