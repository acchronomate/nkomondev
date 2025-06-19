<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirmer')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->record->confirm();
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->success()
                        ->title('Réservation confirmée')
                        ->body('La réservation a été confirmée avec succès.')
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\Action::make('check_in')
                ->label('Enregistrer arrivée')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('primary')
                ->action(function () {
                    $this->record->checkIn();

                    Notification::make()
                        ->success()
                        ->title('Arrivée enregistrée')
                        ->body('L\'arrivée du client a été enregistrée.')
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'confirmed' &&
                    $this->record->check_in->isToday() &&
                    !$this->record->checked_in_at),

            Actions\Action::make('check_out')
                ->label('Enregistrer départ')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->color('info')
                ->action(function () {
                    $this->record->checkOut();
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->success()
                        ->title('Départ enregistré')
                        ->body('Le départ du client a été enregistré. La réservation est maintenant terminée.')
                        ->send();
                })
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'confirmed' &&
                    $this->record->checked_in_at &&
                    !$this->record->checked_out_at),

            Actions\DeleteAction::make()
                ->visible(fn () => in_array($this->record->status, ['pending', 'cancelled'])),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Réservation mise à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function afterSave(): void
    {
        // Si le statut change, mettre à jour l'historique
        if ($this->record->wasChanged('status')) {
            $this->record->logStatusChange($this->record->status);
        }
    }
}
