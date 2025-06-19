<?php

namespace App\Filament\Admin\Resources\AvailabilityResource\Pages;

use App\Filament\Admin\Resources\AvailabilityResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAvailability extends CreateRecord
{
    protected static string $resource = AvailabilityResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Disponibilité créée')
            ->body('La disponibilité a été créée avec succès.');
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        if (($data['date_type'] ?? 'single') === 'range') {
            // Pas de vérification ici, car la création se fait manuellement dans afterCreate
            return;
        }

        // Vérifie pour le mode single AVANT la création
        $exists = \App\Models\Availability::where('room_id', $data['room_id'])
            ->whereDate('date', $data['date'])
            ->exists();

        if ($exists) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Doublon')
                ->body('Une disponibilité existe déjà pour cette chambre et cette date.')
                ->send();

            $this->halt();
        }
    }
}
