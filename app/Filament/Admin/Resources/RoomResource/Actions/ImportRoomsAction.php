<?php

namespace App\Filament\Admin\Resources\RoomResource\Actions;

use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Http\UploadedFile;
use League\Csv\Reader;

class ImportRoomsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Importer des chambres')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                Forms\Components\FileUpload::make('file')
                    ->label('Fichier CSV')
                    ->acceptedFileTypes(['text/csv', 'application/csv'])
                    ->required(),

                Forms\Components\Select::make('accommodation_id')
                    ->label('Hébergement')
                    ->relationship('accommodation', 'name')
                    ->searchable()
                    ->required(),

                Forms\Components\Toggle::make('update_existing')
                    ->label('Mettre à jour les chambres existantes')
                    ->default(false),
            ])
            ->action(function (array $data): void {
                $this->importFromCsv($data);
            });
    }

    protected function importFromCsv(array $data): void
    {
        $file = $data['file'];
        $accommodationId = $data['accommodation_id'];
        $updateExisting = $data['update_existing'];

        // Exemple de structure CSV attendue :
        // name_fr,name_en,room_type,capacity_adults,capacity_children,base_price_per_night,total_quantity

        try {
            $csv = Reader::createFromPath(storage_path('app/public/' . $file));
            $csv->setHeaderOffset(0);

            $imported = 0;
            $updated = 0;

            foreach ($csv as $row) {
                $roomData = [
                    'accommodation_id' => $accommodationId,
                    'name' => [
                        'fr' => $row['name_fr'] ?? '',
                        'en' => $row['name_en'] ?? '',
                    ],
                    'room_type' => $row['room_type'] ?? 'double',
                    'capacity_adults' => (int) ($row['capacity_adults'] ?? 2),
                    'capacity_children' => (int) ($row['capacity_children'] ?? 0),
                    'base_price_per_night' => (float) ($row['base_price_per_night'] ?? 0),
                    'total_quantity' => (int) ($row['total_quantity'] ?? 1),
                ];

                if ($updateExisting) {
                    $room = \App\Models\Room::updateOrCreate(
                        [
                            'accommodation_id' => $accommodationId,
                            'name->fr' => $roomData['name']['fr'],
                        ],
                        $roomData
                    );

                    if ($room->wasRecentlyCreated) {
                        $imported++;
                    } else {
                        $updated++;
                    }
                } else {
                    \App\Models\Room::create($roomData);
                    $imported++;
                }
            }

            $message = "Import terminé : $imported chambres importées";
            if ($updated > 0) {
                $message .= ", $updated chambres mises à jour";
            }

            $this->success($message);

        } catch (\Exception $e) {
            $this->failure('Erreur lors de l\'import : ' . $e->getMessage());
        }
    }
}
