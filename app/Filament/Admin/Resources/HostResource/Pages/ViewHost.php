<?php

namespace App\Filament\Admin\Resources\HostResource\Pages;

use App\Filament\Admin\Resources\HostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHost extends ViewRecord
{
    protected static string $resource = HostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
