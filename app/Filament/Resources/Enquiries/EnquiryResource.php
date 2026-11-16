<?php

namespace App\Filament\Resources\Enquiries;

use App\Filament\Resources\Enquiries\Pages\ManageEnquiries;
use App\Models\Enquiry;
use App\NavigationGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Messages sent through the website's contact form, so they can be read here as well as in the inbox.
 */
class EnquiryResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::ClientsAndBookings;

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->label('Received')
                    ->dateTime('j M Y, H:i'),
                TextEntry::make('source_page')
                    ->label('Sent from')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->url(fn (Enquiry $record): string => "mailto:{$record->email}"),
                TextEntry::make('phone')
                    ->placeholder('-')
                    ->url(fn (Enquiry $record): ?string => $record->phone ? "tel:{$record->phone}" : null),
                TextEntry::make('message')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('j M Y, H:i')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->placeholder('-'),
                TextColumn::make('message')
                    ->limit(60),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEnquiries::route('/'),
        ];
    }
}
