<?php

namespace App\Filament\Resources\GiftCards\Schemas;

use App\GiftCardStatus;
use App\Models\GiftCard;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GiftCardInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Card')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('code')
                            ->copyable(),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('status')
                            ->badge()
                            ->state(fn (GiftCard $record): GiftCardStatus => $record->status()),
                        TextEntry::make('remaining')
                            ->label('Remaining')
                            ->state(fn (GiftCard $record): string => $record->remainingSummary()),
                        TextEntry::make('price_paid_pence')
                            ->label('Price paid')
                            ->money('GBP', divideBy: 100),
                        TextEntry::make('purchaser_name')
                            ->label('Purchaser'),
                        TextEntry::make('recipient_name')
                            ->label('Recipient')
                            ->placeholder('-'),
                        TextEntry::make('sold_at')
                            ->label('Sold on')
                            ->date(),
                        TextEntry::make('expires_at')
                            ->label('Expires on')
                            ->date()
                            ->placeholder('-'),
                    ]),
                Section::make('Preview')
                    ->schema([
                        ViewEntry::make('preview')
                            ->hiddenLabel()
                            ->view('gift-cards.preview'),
                    ]),
            ]);
    }
}
