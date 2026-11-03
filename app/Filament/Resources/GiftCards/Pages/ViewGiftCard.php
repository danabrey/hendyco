<?php

namespace App\Filament\Resources\GiftCards\Pages;

use App\Filament\Resources\GiftCards\GiftCardResource;
use App\Models\GiftCard;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewGiftCard extends ViewRecord
{
    protected static string $resource = GiftCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->icon(Heroicon::OutlinedPrinter)
                ->url(fn (GiftCard $record): string => route('filament.admin.gift-cards.print', $record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
