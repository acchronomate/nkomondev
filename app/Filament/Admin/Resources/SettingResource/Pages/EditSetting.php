<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Cache;
use JsonException;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => static::getResource()::canCreate()),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Paramètre mis à jour')
            ->body('Les modifications ont été enregistrées avec succès.');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Préparer la valeur pour l'affichage selon le type
        if ($data['type'] === 'boolean') {
            $data['value'] = $data['value'] === '1';
        } elseif ($data['type'] === 'json') {
            // Garder en string pour l'édition
        }

        return $data;
    }

    /**
     * @throws Halt
     * @throws JsonException
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Préparer la valeur pour la sauvegarde selon le type
        if ($data['type'] === 'boolean') {
            $data['value'] = $data['value'] ? '1' : '0';
        } elseif ($data['type'] === 'json') {
            // Valider que c'est du JSON valide
            $decoded = json_decode($data['value'], false, 512, JSON_THROW_ON_ERROR);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Notification::make()
                    ->danger()
                    ->title('Erreur JSON')
                    ->body('La valeur doit être un JSON valide.')
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Vider le cache après modification
        Cache::forget('settings.' . $this->record->key);
        Cache::forget('settings.all');
    }
}
