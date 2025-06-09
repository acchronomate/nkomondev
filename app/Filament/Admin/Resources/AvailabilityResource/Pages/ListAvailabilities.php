<?php

namespace App\Filament\Admin\Resources\AvailabilityResource\Pages;

use App\Filament\Admin\Resources\AvailabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAvailabilities extends ListRecords
{
    protected static string $resource = AvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
