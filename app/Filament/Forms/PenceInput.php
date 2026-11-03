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
            ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : $state / 100)
            ->dehydrateStateUsing(fn ($state): int => (int) round(((float) $state) * 100));
    }
}
