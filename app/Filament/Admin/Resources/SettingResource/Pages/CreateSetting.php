<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use JsonException;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Paramètre créé')
            ->body('Le nouveau paramètre a été ajouté avec succès.');
    }

    /**
     * @throws JsonException
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Préparer la valeur selon le type
        if ($data['type'] === 'boolean') {
            $data['value'] = $data['value'] ? '1' : '0';
        } elseif ($data['type'] === 'json' && is_array($data['value'])) {
            $data['value'] = json_encode($data['value'], JSON_THROW_ON_ERROR);
        }

        return $data;
    }
}
