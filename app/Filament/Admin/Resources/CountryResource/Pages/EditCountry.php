<?php

namespace App\Filament\Admin\Resources\CountryResource\Pages;

use App\Filament\Admin\Resources\CountryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression si le pays a des villes
                    if ($this->record->cities()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Ce pays possède des villes. Veuillez d\'abord les supprimer.')
                            ->send();

                        $this->halt();
                    }

                    // Empêcher la suppression si le pays a des hébergements
                    if ($this->record->accommodations()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Ce pays possède des hébergements.')
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
            ->title('Pays mis à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }
}
