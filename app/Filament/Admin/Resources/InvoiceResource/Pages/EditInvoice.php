<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('recalculate')
                ->label('Recalculer les totaux')
                ->icon('heroicon-o-calculator')
                ->action(function () {
                    $this->record->calculateTotals();
                    $this->refreshFormData(['total_bookings', 'total_revenue', 'commission_amount']);

                    Notification::make()
                        ->success()
                        ->title('Totaux recalculés')
                        ->body('Les totaux ont été recalculés depuis les réservations.')
                        ->send();
                })
                ->requiresConfirmation()
                ->color('warning'),

            Actions\Action::make('download_pdf')
                ->label('Télécharger PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => response()->download($this->record->generatePdf()))
                ->color('gray'),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Facture mise à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }
}
