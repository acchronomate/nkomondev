<?php

namespace App\Filament\Admin\Resources\AccommodationResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Accommodation;

class AccommodationStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total hébergements', Accommodation::count())
                ->description('Tous les hébergements')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Hébergements actifs', Accommodation::where('status', 'active')->count())
                ->description('Disponibles à la réservation')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total chambres', Accommodation::withSum('rooms', 'total_quantity')->get()->sum('rooms_sum_total_quantity'))
                ->description('Capacité totale')
                ->descriptionIcon('heroicon-m-home')
                ->color('warning'),

            Stat::make('Note moyenne', number_format(Accommodation::where('rating_average', '>', 0)->avg('rating_average'), 1) . '/5')
                ->description('Sur ' . Accommodation::where('total_reviews', '>', 0)->count() . ' hébergements notés')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),
        ];
    }
}
