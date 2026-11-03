<?php

namespace App\Filament\Resources\Bookings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->time('H:i'),
                TextColumn::make('service.name')
                    ->searchable(),
                TextColumn::make('dogs.name')
                    ->badge(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Filter::make('unpaid')
                    ->label('Unpaid')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereHas('clientRows', fn (Builder $rows) => $rows->unpaid())),
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
