<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Facture créée')
            ->body("La facture a été créée avec succès.");
    }

    protected function afterCreate(): void
    {
        // Calculer les totaux depuis les réservations
        $this->record->calculateTotals();

        // Notifier l'hébergeur
        Notification::make()
            ->title('Nouvelle facture disponible')
            ->body("Votre facture pour {$this->record->period} est disponible.")
            ->sendToDatabase($this->record->user);
    }
}
