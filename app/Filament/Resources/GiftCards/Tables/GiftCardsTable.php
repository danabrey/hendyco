<?php

namespace App\Filament\Resources\GiftCards\Tables;

use App\GiftCardStatus;
use App\Models\GiftCard;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GiftCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('recipient_name')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('purchaser_name')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (GiftCard $record): GiftCardStatus => $record->status()),
                TextColumn::make('remaining')
                    ->state(fn (GiftCard $record): string => $record->remainingSummary()),
                TextColumn::make('sold_at')
                    ->label('Sold on')
                    ->date()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires on')
                    ->date()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->defaultSort('sold_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(GiftCardStatus::class)
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        GiftCardStatus::Available->value => $query->available(),
                        GiftCardStatus::Used->value => $query->used(),
                        GiftCardStatus::Expired->value => $query->expired(),
                        default => $query,
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
