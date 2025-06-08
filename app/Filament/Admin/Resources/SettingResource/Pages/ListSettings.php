<?php

namespace App\Filament\Admin\Resources\SettingResource\Pages;

use App\Filament\Admin\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Setting;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => static::getResource()::canCreate()),

            Actions\Action::make('initialize_defaults')
                ->label('Initialiser les paramètres par défaut')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    Setting::initializeDefaults();

                    $this->notify('success', 'Les paramètres par défaut ont été initialisés.');
                })
                ->requiresConfirmation()
                ->visible(fn () => Setting::count() === 0),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->badge(fn () => Setting::count()),

            'general' => Tab::make('Général')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('key', 'like', 'app_%'))
                ->badge(fn () => Setting::where('key', 'like', 'app_%')->count()),

            'commission' => Tab::make('Commission')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('key', 'like', '%commission%'))
                ->badge(fn () => Setting::where('key', 'like', '%commission%')->count()),

            'booking' => Tab::make('Réservations')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('key', 'like', '%booking%'))
                ->badge(fn () => Setting::where('key', 'like', '%booking%')->count()),

            'notification' => Tab::make('Notifications')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('key', 'like', '%notification%'))
                ->badge(fn () => Setting::where('key', 'like', '%notification%')->count()),
        ];
    }
}
