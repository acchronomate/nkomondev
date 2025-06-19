<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes')
                ->badge(fn () => $this->getModel()::count()),

            'pending' => Tab::make('En attente')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),

            'confirmed' => Tab::make('Confirmées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed'))
                ->badge(fn () => $this->getModel()::where('status', 'confirmed')->count())
                ->badgeColor('success'),

            'cancelled' => Tab::make('Annulées')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(fn () => $this->getModel()::where('status', 'cancelled')->count())
                ->badgeColor('danger'),

            'today_arrivals' => Tab::make('Arrivées aujourd\'hui')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'confirmed')
                    ->whereDate('check_in', now()->toDateString()))
                ->badge(fn () => $this->getModel()::where('status', 'confirmed')
                    ->whereDate('check_in', now()->toDateString())->count())
                ->badgeColor('primary'),

            'today_departures' => Tab::make('Départs aujourd\'hui')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'confirmed')
                    ->whereDate('check_out', now()->toDateString()))
                ->badge(fn () => $this->getModel()::where('status', 'confirmed')
                    ->whereDate('check_out', now()->toDateString())->count())
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BookingResource\Widgets\BookingStats::class,
        ];
    }
}
