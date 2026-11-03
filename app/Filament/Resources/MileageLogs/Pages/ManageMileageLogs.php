<?php

namespace App\Filament\Resources\MileageLogs\Pages;

use App\Filament\Resources\MileageLogs\MileageLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMileageLogs extends ManageRecords
{
    protected static string $resource = MileageLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
