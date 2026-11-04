<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\BookingClient;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Booking')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('date')
                            ->date(),
                        TextEntry::make('start_time')
                            ->time('H:i'),
                        TextEntry::make('duration_minutes')
                            ->suffix(' min')
                            ->placeholder('-'),
                        TextEntry::make('service.name'),
                        TextEntry::make('dogs.name')
                            ->badge(),
                        TextEntry::make('miles')
                            ->label('Mileage')
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                RepeatableEntry::make('clientRows')
                    ->label('Clients')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('client.name')
                            ->label('Name')
                            ->url(fn (BookingClient $record): ?string => $record->client ? ClientResource::getUrl('view', ['record' => $record->client]) : null)
                            ->color('primary'),
                        TextEntry::make('price_pence')
                            ->label('Price')
                            ->money('GBP', divideBy: 100),
                        TextEntry::make('paid_at')
                            ->label('Paid on')
                            ->date()
                            ->placeholder(fn (BookingClient $record): string => $record->paidOnPlaceholder()),
                        TextEntry::make('payment_method')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
