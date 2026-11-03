<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasLabel
{
    case ClientsAndBookings = 'clients_and_bookings';
    case Expenses = 'expenses';
    case Reports = 'reports';
    case Settings = 'settings';

    public function getLabel(): string
    {
        return match ($this) {
            self::ClientsAndBookings => 'Clients & bookings',
            self::Expenses => 'Expenses',
            self::Reports => 'Reports',
            self::Settings => 'Settings',
        };
    }
}
