<?php

namespace App\Filament\Admin\Resources\AccommodationResource\Pages;

use App\Filament\Admin\Resources\AccommodationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAccommodations extends ListRecords
{
    protected static string $resource = AccommodationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->badge(fn () => $this->getModel()::count()),

            'active' => Tab::make('Actifs')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(fn () => $this->getModel()::where('status', 'active')->count())
                ->badgeColor('success'),

            'inactive' => Tab::make('Inactifs')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'inactive'))
                ->badge(fn () => $this->getModel()::where('status', 'inactive')->count())
                ->badgeColor('danger'),

            'suspended' => Tab::make('Suspendus')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'suspended'))
                ->badge(fn () => $this->getModel()::where('status', 'suspended')->count())
                ->badgeColor('warning'),

            'without_rooms' => Tab::make('Sans chambres')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('rooms'))
                ->badge(fn () => $this->getModel()::doesntHave('rooms')->count())
                ->badgeColor('gray'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AccommodationResource\Widgets\AccommodationStats::class,
        ];
    }
}
