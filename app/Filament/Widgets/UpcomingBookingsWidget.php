<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingBookingsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Coming up (next 7 days)';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Booking::query()
                ->with(['service', 'dogs'])
                ->whereBetween('date', [today()->toDateString(), today()->addDays(7)->toDateString()])
                ->orderBy('date')
                ->orderBy('start_time'))
            ->columns([
                TextColumn::make('date')
                    ->formatStateUsing(fn (Booking $record): string => match (true) {
                        $record->date->isToday() => 'Today',
                        $record->date->isTomorrow() => 'Tomorrow',
                        default => $record->date->format('D j M'),
                    }),
                TextColumn::make('start_time')
                    ->label('Time')
                    ->time('H:i'),
                TextColumn::make('service.name')
                    ->label('Service'),
                TextColumn::make('dogs.name')
                    ->label('Dogs')
                    ->badge(),
            ])
            ->recordUrl(fn (Booking $record): string => BookingResource::getUrl('view', ['record' => $record]))
            ->paginated(false)
            ->emptyStateHeading('Nothing booked in the next 7 days');
    }
}
