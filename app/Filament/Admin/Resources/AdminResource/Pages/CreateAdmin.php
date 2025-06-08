<?php

namespace App\Filament\Admin\Resources\AdminResource\Pages;

use App\Filament\Admin\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Administrateur créé')
            ->body('Le compte administrateur a été créé avec succès.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // L'email est automatiquement vérifié pour les admins
        $data['email_verified_at'] = now();
        $data['type'] = 'admin';

        return $data;
    }
}
