<?php

namespace App\Filament\Admin\Resources\BookingResource\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStats extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        return [
            Stat::make('Réservations en attente', Booking::where('status', 'pending')->count())
                ->description('À confirmer')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([7, 5, 10, 3, 15, 8, 12]),

            Stat::make('Arrivées aujourd\'hui', Booking::where('status', 'confirmed')
                ->whereDate('check_in', $today)->count())
                ->description('Check-in prévus')
                ->descriptionIcon('heroicon-m-arrow-right-on-rectangle')
                ->color('primary'),

            Stat::make('Taux d\'occupation', $this->getOccupancyRate() . '%')
                ->description('Aujourd\'hui')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($this->getOccupancyRate() > 80 ? 'success' : 'info'),

            Stat::make('Revenus du mois', $this->getMonthlyRevenue())
                ->description('Commissions incluses')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart($this->getRevenueChart()),
        ];
    }

    protected function getOccupancyRate(): int
    {
        $totalRooms = \App\Models\Room::sum('total_quantity');
        if ($totalRooms === 0) return 0;

        $occupiedRooms = Booking::where('status', 'confirmed')
            ->where('check_in', '<=', now())
            ->where('check_out', '>', now())
            ->count();

        return round(($occupiedRooms / $totalRooms) * 100);
    }

    protected function getMonthlyRevenue(): string
    {
        $revenue = Booking::whereIn('status', ['confirmed', 'completed'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        return number_format($revenue, 0, ',', ' ') . ' FCFA';
    }

    protected function getRevenueChart(): array
    {
        // Données fictives pour le graphique
        return [12000, 15000, 18000, 22000, 19000, 25000, 28000];
    }
}
