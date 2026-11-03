<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\Client;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClientInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('emergency_contact_name')
                    ->placeholder('-'),
                TextEntry::make('emergency_contact_phone')
                    ->placeholder('-'),
                TextEntry::make('archived_at')
                    ->dateTime()
                    ->visible(fn (Client $record): bool => $record->isArchived()),
            ]);
    }
}
