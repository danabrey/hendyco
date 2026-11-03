<?php

namespace App\Filament\Resources\Clients\Tables;

use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('dogs.name')
                    ->label('Dogs')
                    ->badge()
                    ->searchable(),
                TextColumn::make('archived_at')
                    ->label('Archived')
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('archived_at')
                    ->label('Archived')
                    ->nullable()
                    ->default(false)
                    ->trueLabel('Archived only')
                    ->falseLabel('Active only')
                    ->placeholder('All'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('archive')
                    ->icon('heroicon-o-archive-box')
                    ->requiresConfirmation()
                    ->visible(fn (Client $record): bool => ! $record->isArchived() && ! $record->trashed())
                    ->action(fn (Client $record) => $record->archive()),
                Action::make('unarchive')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->visible(fn (Client $record): bool => $record->isArchived() && ! $record->trashed())
                    ->action(fn (Client $record) => $record->unarchive()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
