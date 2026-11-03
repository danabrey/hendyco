<?php

namespace App\Filament\Resources\GiftCards\Schemas;

use App\Filament\Forms\PenceInput;
use App\GiftCardType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class GiftCardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Generated when saved')
                    ->visibleOn('edit'),
                Select::make('type')
                    ->options(GiftCardType::class)
                    ->required()
                    ->live()
                    ->disabledOn('edit')
                    ->default(GiftCardType::Money),
                PenceInput::make('value_pence')
                    ->label('Value')
                    ->required()
                    ->visible(fn (Get $get): bool => static::isType($get('type'), GiftCardType::Money)),
                Repeater::make('lines')
                    ->relationship()
                    ->schema([
                        Select::make('service_id')
                            ->relationship('service', 'name')
                            ->required()
                            ->preload(),
                        TextInput::make('quantity')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->default(1),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->required(fn (Get $get): bool => static::isType($get('type'), GiftCardType::Service))
                    ->visible(fn (Get $get): bool => static::isType($get('type'), GiftCardType::Service))
                    ->columnSpanFull(),
                PenceInput::make('price_paid_pence')
                    ->label('Price paid')
                    ->required(),
                DatePicker::make('sold_at')
                    ->label('Sold on')
                    ->required()
                    ->default(today()),
                TextInput::make('purchaser_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('recipient_name')
                    ->maxLength(255),
                DatePicker::make('expires_at')
                    ->label('Expires on'),
            ]);
    }

    protected static function isType(mixed $state, GiftCardType $type): bool
    {
        return ($state instanceof GiftCardType ? $state : GiftCardType::tryFrom((string) $state)) === $type;
    }
}
