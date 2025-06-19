<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Booking;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class GenerateMonthlyInvoices extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.admin.resources.invoice-resource.pages.generate-monthly-invoices';

    protected static ?string $title = 'Générer les factures mensuelles';

    public ?int $month = null;
    public ?int $year = null;
    public ?array $previewData = null;

    public function mount(): void
    {
        $this->month = now()->subMonth()->month;
        $this->year = now()->subMonth()->year;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Période de facturation')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('month')
                                    ->label('Mois')
                                    ->options([
                                        1 => 'Janvier',
                                        2 => 'Février',
                                        3 => 'Mars',
                                        4 => 'Avril',
                                        5 => 'Mai',
                                        6 => 'Juin',
                                        7 => 'Juillet',
                                        8 => 'Août',
                                        9 => 'Septembre',
                                        10 => 'Octobre',
                                        11 => 'Novembre',
                                        12 => 'Décembre',
                                    ])
                                    ->required()
                                    ->reactive(),

                                Forms\Components\Select::make('year')
                                    ->label('Année')
                                    ->options(function () {
                                        $years = [];
                                        $currentYear = now()->year;
                                        for ($year = $currentYear - 1; $year <= $currentYear; $year++) {
                                            $years[$year] = $year;
                                        }
                                        return $years;
                                    })
                                    ->required()
                                    ->reactive(),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function preview(): void
    {
        $this->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
        ]);

        // Vérifier les factures existantes
        $existingInvoices = Invoice::where('month', $this->month)
            ->where('year', $this->year)
            ->pluck('user_id');

        // Récupérer les hébergeurs avec des réservations complétées
        $hosts = User::where('type', 'host')
            ->whereHas('accommodations.bookings', function ($query) {
                $query->where('status', 'completed')
                    ->whereMonth('checked_out_at', $this->month)
                    ->whereYear('checked_out_at', $this->year);
            })
            ->whereNotIn('id', $existingInvoices)
            ->with(['accommodations.bookings' => function ($query) {
                $query->where('status', 'completed')
                    ->whereMonth('checked_out_at', $this->month)
                    ->whereYear('checked_out_at', $this->year);
            }])
            ->get();

        $this->previewData = $hosts->map(function ($host) {
            $bookings = collect();
            foreach ($host->accommodations as $accommodation) {
                $bookings = $bookings->merge($accommodation->bookings);
            }

            $totalRevenue = $bookings->sum('total_amount');
            $totalCommission = $bookings->sum('commission_amount');

            return [
                'host_id' => $host->id,
                'host_name' => $host->name,
                'bookings_count' => $bookings->count(),
                'total_revenue' => $totalRevenue,
                'commission_amount' => $totalCommission,
                'currency' => $host->preferredCurrency->code,
            ];
        })->toArray();
    }

    public function generate(): void
    {
        if (empty($this->previewData)) {
            Notification::make()
                ->warning()
                ->title('Aucune facture à générer')
                ->body('Veuillez d\'abord prévisualiser les factures.')
                ->send();
            return;
        }

        $generated = 0;

        foreach ($this->previewData as $data) {
            $host = User::find($data['host_id']);

            $invoice = Invoice::create([
                'user_id' => $host->id,
                'month' => $this->month,
                'year' => $this->year,
                'total_bookings' => $data['bookings_count'],
                'total_revenue' => $data['total_revenue'],
                'commission_rate' => 5.00,
                'commission_amount' => $data['commission_amount'],
                'currency_id' => $host->preferred_currency_id,
                'exchange_rate_used' => $host->preferredCurrency->exchange_rate,
                'status' => 'draft',
            ]);

            // Calculer les détails
            $invoice->calculateTotals();

            $generated++;
        }

        Notification::make()
            ->success()
            ->title('Factures générées')
            ->body("$generated factures ont été générées avec succès.")
            ->send();

        $this->redirect(InvoiceResource::getUrl('index'));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Retour')
                ->url(InvoiceResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
