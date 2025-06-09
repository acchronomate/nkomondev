<?php

namespace App\Filament\Admin\Resources\AccommodationResource\Pages;

use App\Filament\Admin\Resources\AccommodationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAccommodation extends ViewRecord
{
    protected static string $resource = AccommodationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
