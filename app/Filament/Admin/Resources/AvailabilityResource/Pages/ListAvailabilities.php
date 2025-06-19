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
            Actions\Action::make('calendar_view')
                ->label('Vue calendrier')
                ->icon('heroicon-o-calendar')
                ->url(fn () => AvailabilityResource::getUrl('calendar'))
                ->color('primary'),

            Actions\Action::make('bulk_update')
                ->label('Mise à jour en masse')
                ->icon('heroicon-o-pencil-square')
                ->action(fn () => $this->bulkUpdateAvailabilities())
                ->color('warning'),
        ];
    }

    protected function bulkUpdateAvailabilities(): void
    {
        // Logique pour mise à jour en masse
        $this->notify('success', 'Fonctionnalité de mise à jour en masse en cours de développement.');
    }
}
