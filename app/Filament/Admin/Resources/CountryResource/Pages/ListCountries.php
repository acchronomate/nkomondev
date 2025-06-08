<?php

namespace App\Filament\Admin\Resources\CountryResource\Pages;

use App\Filament\Admin\Resources\CountryResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCountries extends ListRecords
{
    protected static string $resource = CountryResource::class;

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
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => $this->getModel()::where('is_active', true)->count())
                ->badgeColor('success'),

            'with_accommodations' => Tab::make('Avec hébergements')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('accommodations'))
                ->badge(fn () => $this->getModel()::has('accommodations')->count())
                ->badgeColor('primary'),
        ];
    }
}
