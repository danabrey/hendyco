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
            ->formatStateUsing(fn (mixed $state): ?float => static::toPounds($state))
            ->dehydrateStateUsing(fn (mixed $state): int => static::toPence($state));
    }

    /**
     * The pounds shown in the input for a pence amount, or null when there is no amount.
     */
    public static function toPounds(mixed $pence): ?float
    {
        return is_numeric($pence) ? $pence / 100 : null;
    }

    /**
     * The pence stored for a pounds amount typed into the input, or 0 when nothing usable was typed.
     */
    public static function toPence(mixed $pounds): int
    {
        return is_numeric($pounds) ? (int) round(((float) $pounds) * 100) : 0;
    }
}
