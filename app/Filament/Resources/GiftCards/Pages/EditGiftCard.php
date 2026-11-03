<?php

namespace App\Filament\Resources\GiftCards\Pages;

use App\Filament\Resources\GiftCards\GiftCardResource;
use App\Models\GiftCard;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditGiftCard extends EditRecord
{
    protected static string $resource = GiftCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('print')
                ->icon(Heroicon::OutlinedPrinter)
                ->url(fn (GiftCard $record): string => route('filament.admin.gift-cards.print', $record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
