<?php

namespace App\Filament\Admin\Resources\AdminResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Administrators', 'admin_count')
                ->label('Administrateurs')
                ->value(\App\Models\User::where('type', 'admin')->count())
                ->icon('heroicon-o-shield-check')
                ->color('primary')
                ->description('Nombre total d\'administrateurs'),

            Stat::make('Total Active Administrators', 'active_admin_count')
                ->label('Administrateurs Actifs')
                ->value(\App\Models\User::where('type', 'admin')->where('is_active', true)->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('Nombre d\'administrateurs actifs'),

            Stat::make('Total Inactive Administrators', 'inactive_admin_count')
                ->label('Administrateurs Inactifs')
                ->value(\App\Models\User::where('type', 'admin')->where('is_active', false)->count())
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description('Nombre d\'administrateurs inactifs'),
        ];
    }
}
