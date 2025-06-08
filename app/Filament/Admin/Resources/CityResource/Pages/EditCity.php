<?php

namespace App\Filament\Admin\Resources\CityResource\Pages;

use App\Filament\Admin\Resources\CityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCity extends EditRecord
{
    protected static string $resource = CityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression si la ville a des quartiers ou hébergements
                    if ($this->record->districts()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Cette ville possède des quartiers. Veuillez d\'abord les supprimer.')
                            ->send();

                        $this->halt();
                    }

                    if ($this->record->accommodations()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Cette ville possède des hébergements. Veuillez d\'abord les transférer ou supprimer.')
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
            ->title('Ville mise à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }
}
