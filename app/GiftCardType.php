<?php

namespace App;

use Filament\Support\Contracts\HasLabel;

enum GiftCardType: string implements HasLabel
{
    case Money = 'money';
    case Service = 'service';

    public function getLabel(): string
    {
        return match ($this) {
            self::Money => 'Money',
            self::Service => 'Services',
        };
    }
}
