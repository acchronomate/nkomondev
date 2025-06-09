<?php

namespace App\Filament\Admin\Resources\RoomResource\Pages;

use App\Filament\Admin\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manage_availability')
                ->label('Gérer les disponibilités')
                ->icon('heroicon-o-calendar-days')
                ->url(fn () => route('filament.admin.resources.availabilities.index', ['room' => $this->record->id]))
                ->color('warning'),

            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression si des réservations existent
                    if ($this->record->bookings()->whereIn('status', ['pending', 'confirmed'])->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Cette chambre a des réservations actives et ne peut pas être supprimée.')
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
            ->title('Chambre mise à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }
}
