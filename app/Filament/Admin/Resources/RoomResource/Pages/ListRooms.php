<?php

namespace App\Filament\Admin\Resources\RoomResource\Pages;

use App\Filament\Admin\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('Importer')
                ->icon('heroicon-o-arrow-up-tray')
                ->action(fn () => $this->importRooms())
                ->color('gray'),
        ];
    }

    protected function importRooms(): void
    {
        // Logique d'import à implémenter
        $this->notify('success', 'Fonctionnalité d\'import en cours de développement.');
    }
}
