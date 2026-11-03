<?php

namespace App\Filament\Resources\GiftCards\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RedemptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'redemptions';

    protected static ?string $title = 'Redemptions';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bookingClient.booking.date')
                    ->label('Booking date')
                    ->date(),
                TextColumn::make('bookingClient.client.name')
                    ->label('Client'),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->placeholder('-'),
                TextColumn::make('amount_pence')
                    ->label('Covered')
                    ->money('GBP', divideBy: 100),
            ])
            ->modifyQueryUsing(fn ($query) => $query->whereHas('bookingClient.booking')->with('bookingClient.booking', 'bookingClient.client'))
            ->defaultSort('id', 'desc');
    }
}
