<?php

namespace App\Filament\Admin\Resources\RoomResource\Pages;

use App\Filament\Admin\Resources\RoomResource;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
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
                ->form([
                    FileUpload::make('file')
                        ->label('Fichier CSV')
                        ->acceptedFileTypes(['text/csv', 'application/csv'])
                        ->required()
                        ->helperText('Format: name_fr, name_en, room_type, capacity_adults, capacity_children, base_price_per_night, total_quantity'),

                    Select::make('accommodation_id')
                        ->label('Hébergement')
                        ->options(\App\Models\Accommodation::pluck('name', 'id'))
                        ->searchable()
                        ->required(),

                    Toggle::make('update_existing')
                        ->label('Mettre à jour les chambres existantes')
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    // Logique d'import simplifiée
                    $this->notify('success', 'Import en cours de développement. Fichier reçu : ' . $data['file']);
                })
                ->modalWidth('lg')
                ->color('gray'),
        ];
    }
}
