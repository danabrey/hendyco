<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Forms\PenceInput;
use App\Models\Dog;
use App\Models\Service;
use App\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->required()
                    ->default(fn () => request()->query('date', today()->toDateString())),
                TimePicker::make('start_time')
                    ->required()
                    ->seconds(false),
                TextInput::make('duration_minutes')
                    ->label('Duration (minutes)')
                    ->numeric()
                    ->minValue(1),
                Select::make('service_id')
                    ->relationship('service', 'name')
                    ->required()
                    ->live(),
                Select::make('dogs')
                    ->relationship('dogs', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Dog $record): string => "{$record->name} ({$record->client->name})")
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
                TextInput::make('miles')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),
                Textarea::make('notes')
                    ->columnSpanFull(),
                Repeater::make('clientRows')
                    ->label('Clients')
                    ->relationship()
                    ->columnSpanFull()
                    ->columns(4)
                    ->defaultItems(0)
                    ->addActionLabel('Add client')
                    ->schema([
                        Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        PenceInput::make('price_pence')
                            ->label('Price')
                            ->required()
                            ->default(fn (Get $get): int => Service::find($get('../../service_id'))?->default_price_pence ?? 0),
                        DatePicker::make('paid_at')
                            ->label('Paid on'),
                        Select::make('payment_method')
                            ->options(PaymentMethod::class),
                    ]),
            ]);
    }
}
