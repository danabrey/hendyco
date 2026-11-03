<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingsCalendarWidget;
use App\NavigationGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BookingsCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::ClientsAndBookings;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Calendar';

    protected string $view = 'filament.pages.bookings-calendar';

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [BookingsCalendarWidget::class];
    }
}
