<?php

namespace App\Filament\Admin\Resources\HostResource\Pages;

use App\Filament\Admin\Resources\HostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditHost extends EditRecord
{
    protected static string $resource = HostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_accommodations')
                ->label('Voir les hébergements')
                ->icon('heroicon-o-building-office')
                ->url(fn () => route('filament.admin.resources.accommodations.index', ['host' => $this->record->id]))
                ->visible(fn () => $this->record->accommodations()->count() > 0),

            Actions\Action::make('view_invoices')
                ->label('Voir les factures')
                ->icon('heroicon-o-document-text')
                ->url(fn () => route('filament.admin.resources.invoices.index', ['user' => $this->record->id]))
                ->visible(fn () => $this->record->invoices()->count() > 0),

            Actions\DeleteAction::make()
                ->before(function () {
                    // Empêcher la suppression si l'hébergeur a des hébergements
                    if ($this->record->accommodations()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Suppression impossible')
                            ->body('Cet hébergeur possède des hébergements. Veuillez d\'abord supprimer ou transférer ses hébergements.')
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
            ->title('Hébergeur mis à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password']);

        return $data;
    }
}
