<?php

namespace App\Filament\Admin\Resources\CurrencyResource\Pages;

use App\Filament\Admin\Resources\CurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCurrencies extends ListRecords
{
    protected static string $resource = CurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('update_all_rates')
                ->label('Mettre à jour tous les taux')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->updateAllRates())
                ->requiresConfirmation()
                ->modalHeading('Mettre à jour tous les taux de change')
                ->modalDescription('Cette action ouvrira un formulaire pour mettre à jour tous les taux de change en une seule fois.')
                ->modalSubmitActionLabel('Continuer')
                ->color('warning'),
        ];
    }

    protected function updateAllRates(): void
    {
        // Logique pour mettre à jour tous les taux
        // Pourrait ouvrir un modal avec un formulaire pour tous les taux
        $this->notify('success', 'Fonctionnalité en cours de développement');
    }
}
