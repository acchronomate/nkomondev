<?php

namespace App\Filament\Admin\Resources\AvailabilityResource\Pages;

use App\Filament\Admin\Resources\AvailabilityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAvailability extends EditRecord
{
    protected static string $resource = AvailabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression si la date est passée
                    if ($this->record->date->isPast()) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Impossible de supprimer une disponibilité pour une date passée.')
                            ->send();

                        $this->halt();
                    }
                }),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Disponibilité mise à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pré-remplir l'hébergement basé sur la chambre
        if ($this->record->room) {
            $data['accommodation_id'] = $this->record->room->accommodation_id;
        }

        return $data;
    }
}
