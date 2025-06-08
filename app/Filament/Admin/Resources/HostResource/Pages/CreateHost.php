<?php

namespace App\Filament\Admin\Resources\HostResource\Pages;

use App\Filament\Admin\Resources\HostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\Notification as NotificationModel;

class CreateHost extends CreateRecord
{
    protected static string $resource = HostResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Hébergeur créé')
            ->body('Le compte hébergeur a été créé avec succès.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = 'host';

        return $data;
    }

    protected function afterCreate(): void
    {
        // Envoyer une notification de bienvenue à l'hébergeur
        NotificationModel::create([
            'user_id' => $this->record->id,
            'type' => 'welcome_host',
            'title' => [
                'fr' => 'Bienvenue sur NKOMON',
                'en' => 'Welcome to NKOMON',
            ],
            'message' => [
                'fr' => 'Votre compte hébergeur a été créé. Vous pouvez maintenant ajouter vos établissements.',
                'en' => 'Your host account has been created. You can now add your accommodations.',
            ],
        ]);
    }
}
