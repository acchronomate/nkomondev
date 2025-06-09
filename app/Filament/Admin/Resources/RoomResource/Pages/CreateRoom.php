<?php

namespace App\Filament\Admin\Resources\RoomResource\Pages;

use App\Filament\Admin\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Availability;
use Filament\Notifications\Notification;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Chambre créée')
            ->body('La chambre a été créée avec succès.');
    }

    protected function afterCreate(): void
    {
        // Créer les disponibilités pour les 3 prochains mois
        $startDate = now();
        $endDate = now()->addMonths(3);

        while ($startDate <= $endDate) {
            Availability::create([
                'room_id' => $this->record->id,
                'date' => $startDate->format('Y-m-d'),
                'available_quantity' => $this->record->total_quantity,
                'price_override' => null,
                'is_blocked' => false,
            ]);

            $startDate->addDay();
        }
    }
}
