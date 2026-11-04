<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Models\BookingClient;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingRowsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookingRows';

    protected static ?string $title = 'Bookings';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking.date')
                    ->label('Date')
                    ->date(),
                TextColumn::make('booking.service.name')
                    ->label('Service'),
                TextColumn::make('price_pence')
                    ->label('Price')
                    ->money('GBP', divideBy: 100),
                TextColumn::make('paid_at')
                    ->label('Paid on')
                    ->date()
                    ->placeholder(fn (BookingClient $record): string => $record->paidOnPlaceholder()),
                TextColumn::make('payment_method'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->whereHas('booking'))
            ->defaultSort('id', 'desc');
    }
}
