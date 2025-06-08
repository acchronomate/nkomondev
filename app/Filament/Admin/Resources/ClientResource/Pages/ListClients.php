<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export')
                ->label('Exporter')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportClients())
                ->color('gray'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->badge(fn () => $this->getModel()::where('type', 'client')->count()),

            'active' => Tab::make('Actifs')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => $this->getModel()::where('type', 'client')->where('is_active', true)->count())
                ->badgeColor('success'),

            'new' => Tab::make('Nouveaux')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('bookings'))
                ->badge(fn () => $this->getModel()::where('type', 'client')->doesntHave('bookings')->count())
                ->badgeColor('gray'),

            'occasional' => Tab::make('Occasionnels')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('bookings', '=', 1))
                ->badge(fn () => $this->getModel()::where('type', 'client')->has('bookings', '=', 1)->count())
                ->badgeColor('warning'),

            'regular' => Tab::make('Réguliers')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('bookings', '>=', 2)->has('bookings', '<=', 4))
                ->badge(fn () => $this->getModel()::where('type', 'client')->has('bookings', '>=', 2)->has('bookings', '<=', 4)->count())
                ->badgeColor('primary'),

            'loyal' => Tab::make('Fidèles')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('bookings', '>=', 5))
                ->badge(fn () => $this->getModel()::where('type', 'client')->has('bookings', '>=', 5)->count())
                ->badgeColor('success'),
        ];
    }

    protected function exportClients()
    {
        $clients = $this->getModel()::where('type', 'client')->get();

        $csvData = $clients->map(function ($client) {
            return [
                'Nom' => $client->name,
                'Email' => $client->email,
                'Téléphone' => $client->phone,
                'Statut' => $client->is_active ? 'Actif' : 'Inactif',
                'Réservations' => $client->bookings()->count(),
            ];
        });

        $filename = 'clients_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($csvData) {
            $handle = fopen('php://output', 'w');
            if ($csvData->isNotEmpty()) {
                fputcsv($handle, array_keys($csvData->first()));
                foreach ($csvData as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
