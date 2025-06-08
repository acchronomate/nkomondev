<?php

namespace App\Filament\Admin\Resources\CityResource\Pages;

use App\Filament\Admin\Resources\CityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCities extends ListRecords
{
    protected static string $resource = CityResource::class;

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

            'active' => Tab::make('Actives')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => $this->getModel()::where('is_active', true)->count())
                ->badgeColor('success'),

            'popular' => Tab::make('Populaires')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_popular', true))
                ->badge(fn () => $this->getModel()::where('is_popular', true)->count())
                ->badgeColor('warning'),

            'with_accommodations' => Tab::make('Avec hébergements')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('accommodations'))
                ->badge(fn () => $this->getModel()::has('accommodations')->count())
                ->badgeColor('primary'),
        ];
    }
}
