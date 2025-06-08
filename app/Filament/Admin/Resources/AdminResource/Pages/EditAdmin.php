<?php

namespace App\Filament\Admin\Resources\AdminResource\Pages;

use App\Filament\Admin\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression de son propre compte
                    if ($this->record->id === auth()->id()) {
                        Notification::make()
                            ->danger()
                            ->title('Action interdite')
                            ->body('Vous ne pouvez pas supprimer votre propre compte.')
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
            ->title('Administrateur mis à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ne pas afficher le mot de passe hashé
        unset($data['password']);

        return $data;
    }
}
