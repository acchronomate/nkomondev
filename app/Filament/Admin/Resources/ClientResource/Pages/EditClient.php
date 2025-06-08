<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_bookings')
                ->label('Voir les réservations')
                ->icon('heroicon-o-calendar-days')
                ->url(fn () => route('filament.admin.resources.bookings.index', ['client' => $this->record->id]))
                ->visible(fn () => $this->record->bookings()->count() > 0),

            Actions\Action::make('view_reviews')
                ->label('Voir les avis')
                ->icon('heroicon-o-star')
                ->url(fn () => route('filament.admin.resources.reviews.index', ['user' => $this->record->id]))
                ->visible(fn () => $this->record->reviews()->count() > 0),

            Actions\DeleteAction::make()
                ->before(function () {
                    // Avertir si le client a des réservations
                    if ($this->record->bookings()->whereIn('status', ['pending', 'confirmed'])->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Ce client a des réservations en cours. Veuillez d\'abord les annuler.')
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
            ->title('Client mis à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password']);

        return $data;
    }
}
