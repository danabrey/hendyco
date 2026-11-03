<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasLabel
{
    case ClientsAndBookings = 'clients_and_bookings';
    case Money = 'money';
    case Reports = 'reports';
    case Settings = 'settings';

    public function getLabel(): string
    {
        return match ($this) {
            self::ClientsAndBookings => 'Clients & bookings',
            self::Money => 'Money',
            self::Reports => 'Reports',
            self::Settings => 'Settings',
        };
    }
}
