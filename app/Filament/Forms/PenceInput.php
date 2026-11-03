<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\TextInput;

class PenceInput
{
    /**
     * A £ input bound to an integer pence column.
     */
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->numeric()
            ->prefix('£')
            ->minValue(0)
            ->step(0.01)
            ->formatStateUsing(fn ($state): ?float => is_numeric($state) ? $state / 100 : null)
            ->dehydrateStateUsing(fn ($state): int => is_numeric($state) ? (int) round(((float) $state) * 100) : 0);
    }
}
