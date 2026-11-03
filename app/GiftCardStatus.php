<?php

namespace App;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum GiftCardStatus: string implements HasColor, HasLabel
{
    case Available = 'available';
    case Used = 'used';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Used => 'Used',
            self::Expired => 'Expired',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Used => 'gray',
            self::Expired => 'danger',
        };
    }
}
