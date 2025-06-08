<?php

namespace App\Filament\Admin\Resources\DistrictResource\Pages;

use App\Filament\Admin\Resources\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDistricts extends ListRecords
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('Importer')
                ->icon('heroicon-o-arrow-up-tray')
                ->action(fn () => $this->importDistricts())
                ->color('gray'),
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

            'empty' => Tab::make('Sans hébergements')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('accommodations'))
                ->badge(fn () => $this->getModel()::doesntHave('accommodations')->count())
                ->badgeColor('gray'),
        ];
    }

    protected function importDistricts(): void
    {
        // Logique d'import à implémenter
        $this->notify('success', 'Import en cours...');
    }
}
