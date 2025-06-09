<?php

namespace App\Filament\Admin\Resources\AccommodationResource\Pages;

use App\Filament\Admin\Resources\AccommodationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAccommodation extends CreateRecord
{
    protected static string $resource = AccommodationResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Hébergement créé')
            ->body('L\'hébergement a été créé avec succès. Vous pouvez maintenant ajouter des chambres.');
    }

    protected function afterCreate(): void
    {
        // Notifier l'hébergeur
        Notification::make()
            ->title('Nouvel hébergement créé')
            ->body("L'hébergement {$this->record->name} a été créé avec succès.")
            ->sendToDatabase($this->record->user);
    }
}
