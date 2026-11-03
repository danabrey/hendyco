<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\BookingClient;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UnpaidBookingsWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Unpaid bookings';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BookingClient::query()
                ->unpaid()
                ->whereHas('booking')
                ->with(['client', 'booking.service', 'giftCardRedemption'])
                ->orderBy(Booking::select('date')->whereColumn('bookings.id', 'booking_clients.booking_id'))
                ->orderBy('booking_clients.id'))
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client'),
                TextColumn::make('booking.date')
                    ->label('Booking date')
                    ->date(),
                TextColumn::make('booking.service.name')
                    ->label('Service'),
                TextColumn::make('owed')
                    ->label('Owed')
                    ->state(fn (BookingClient $record): int => $record->outstandingPence())
                    ->money('GBP', divideBy: 100),
                TextColumn::make('days_outstanding')
                    ->label('Days outstanding')
                    ->state(fn (BookingClient $record): int => (int) $record->booking->date->startOfDay()->diffInDays(today(), false)),
            ])
            ->paginated([10, 25])
            ->emptyStateHeading('Nothing outstanding');
    }
}
