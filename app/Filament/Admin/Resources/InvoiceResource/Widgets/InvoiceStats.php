<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Invoice;

class InvoiceStats extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        return [
            Stat::make('Factures en attente', Invoice::where('status', 'sent')->count())
                ->description('À payer')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Factures en retard', Invoice::overdue()->count())
                ->description('Échéance dépassée')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Commissions du mois', $this->getCurrentMonthCommissions())
                ->description('À percevoir')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Taux de paiement', $this->getPaymentRate() . '%')
                ->description('Sur les 3 derniers mois')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary')
                ->chart($this->getPaymentRateChart()),
        ];
    }

    protected function getCurrentMonthCommissions(): string
    {
        $total = Invoice::where('month', now()->month)
            ->where('year', now()->year)
            ->where('status', '!=', 'draft')
            ->sum('commission_amount');

        return number_format($total, 0, ',', ' ') . ' FCFA';
    }

    protected function getPaymentRate(): float
    {
        $totalInvoices = Invoice::where('status', 'paid')->count();
        $totalSent = Invoice::where('status', 'sent')->count();

        return $totalSent > 0 ? round(($totalInvoices / $totalSent) * 100, 2) : 0.0;
    }

    protected function getPaymentRateChart(): array
    {
        $data = Invoice::selectRaw('MONTH(paid_at) as month, COUNT(*) as count')
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', 2025)
            ->groupByRaw('MONTH(paid_at)')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[$i] = $data[$i] ?? 0;
        }

        return [
            'labels' => array_keys($chartData),
            'datasets' => [
                [
                    'label' => 'Paiements',
                    'data' => array_values($chartData),
                    'backgroundColor' => '#4F46E5',
                ],
            ],
        ];
    }

    protected function getHeader(): ?string
    {
        return 'Statistiques des factures';
    }
}
