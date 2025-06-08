<?php

namespace App\Filament\Admin\Resources\CurrencyResource\Pages;

use App\Filament\Admin\Resources\CurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\ExchangeRateHistory;
use Filament\Notifications\Notification;

class EditCurrency extends EditRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression de la devise par défaut
                    if ($this->record->is_default) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('La devise par défaut ne peut pas être supprimée.')
                            ->send();

                        $this->halt();
                    }

                    // Empêcher la suppression si utilisée
                    if ($this->record->users()->count() > 0 ||
                        $this->record->accommodations()->count() > 0 ||
                        $this->record->bookings()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Cette devise est utilisée et ne peut pas être supprimée.')
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
            ->title('Devise mise à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function afterSave(): void
    {
        // Si le taux de change a été modifié, l'enregistrer dans l'historique
        if ($this->record->wasChanged('exchange_rate')) {
            ExchangeRateHistory::create([
                'currency_id' => $this->record->id,
                'rate' => $this->record->exchange_rate,
                'changed_by' => auth()->id(),
            ]);
        }
    }
}
