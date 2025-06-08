<?php

namespace App\Filament\Admin\Resources\HostResource\Pages;

use App\Filament\Admin\Resources\HostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListHosts extends ListRecords
{
    protected static string $resource = HostResource::class;

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
                ->badge(fn () => $this->getModel()::where('type', 'host')->count()),

            'active' => Tab::make('Actifs')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(fn () => $this->getModel()::where('type', 'host')->where('is_active', true)->count())
                ->badgeColor('success'),

            'verified' => Tab::make('Vérifiés')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('email_verified_at'))
                ->badge(fn () => $this->getModel()::where('type', 'host')->whereNotNull('email_verified_at')->count())
                ->badgeColor('primary'),

            'with_accommodations' => Tab::make('Avec hébergements')
                ->modifyQueryUsing(fn (Builder $query) => $query->has('accommodations'))
                ->badge(fn () => $this->getModel()::where('type', 'host')->has('accommodations')->count())
                ->badgeColor('warning'),

            'new' => Tab::make('Nouveaux')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('accommodations'))
                ->badge(fn () => $this->getModel()::where('type', 'host')->doesntHave('accommodations')->count())
                ->badgeColor('gray'),
        ];
    }
}
