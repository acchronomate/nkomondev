<?php

namespace App\Filament\Admin\Resources\AccommodationResource\Pages;

use App\Filament\Admin\Resources\AccommodationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAccommodation extends EditRecord
{
    protected static string $resource = AccommodationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_rooms')
                ->label('Gérer les chambres')
                ->icon('heroicon-o-home')
                ->url(fn () => route('filament.admin.resources.rooms.index', ['accommodation' => $this->record->id])),

            Actions\Action::make('view_on_site')
                ->label('Voir sur le site')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('accommodation.show', $this->record->slug))
                ->openUrlInNewTab()
                ->visible(fn () => $this->record->status === 'active'),

            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression si des réservations existent
                    if ($this->record->bookings()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Cet hébergement a des réservations et ne peut pas être supprimé.')
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
            ->title('Hébergement mis à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pré-remplir le pays basé sur la ville
        if ($this->record->city) {
            $data['country_id'] = $this->record->city->country_id;
        }

        return $data;
    }
}
