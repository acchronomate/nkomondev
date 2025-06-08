<?php

namespace App\Filament\Admin\Resources\DistrictResource\Pages;

use App\Filament\Admin\Resources\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDistrict extends EditRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression si le quartier a des hébergements
                    if ($this->record->accommodations()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Ce quartier possède des hébergements. Veuillez d\'abord les transférer ou supprimer.')
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
            ->title('Quartier mis à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pré-remplir le pays basé sur la ville
        if ($this->record->city) {
            $data['country_id'] = $this->record->city->country_id;
        }

        return $data;
    }
}
