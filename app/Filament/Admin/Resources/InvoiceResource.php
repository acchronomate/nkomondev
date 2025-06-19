<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Support\Colors\Color;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Gestion financière';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Facture';

    protected static ?string $pluralModelLabel = 'Factures';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de facturation')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Numéro de facture')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('user_id')
                            ->label('Hébergeur')
                            ->relationship('user', 'name', fn ($query) => $query->where('type', 'host'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($operation) => $operation === 'edit'),

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
                                    ->disabled(fn ($operation) => $operation === 'edit'),

                                Forms\Components\Select::make('year')
                                    ->label('Année')
                                    ->options(function () {
                                        $years = [];
                                        $currentYear = now()->year;
                                        for ($year = $currentYear - 2; $year <= $currentYear; $year++) {
                                            $years[$year] = $year;
                                        }
                                        return $years;
                                    })
                                    ->required()
                                    ->default(now()->year)
                                    ->disabled(fn ($operation) => $operation === 'edit'),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Détails financiers')
                    ->schema([
                        Forms\Components\Placeholder::make('total_bookings_display')
                            ->label('Nombre de réservations')
                            ->content(fn ($record) => $record ? $record->total_bookings : '-'),

                        Forms\Components\Placeholder::make('total_revenue_display')
                            ->label('Chiffre d\'affaires total')
                            ->content(fn ($record) => $record ? $record->currency->format($record->total_revenue) : '-'),

                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Taux de commission (%)')
                            ->numeric()
                            ->default(5.00)
                            ->disabled()
                            ->suffix('%'),

                        Forms\Components\Placeholder::make('commission_amount_display')
                            ->label('Montant de la commission')
                            ->content(fn ($record) => $record ? $record->currency->format($record->commission_amount) : '-'),

                        Forms\Components\Select::make('currency_id')
                            ->label('Devise')
                            ->relationship('currency', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($operation) => $operation === 'edit'),

                        Forms\Components\Placeholder::make('exchange_rate_display')
                            ->label('Taux de change utilisé')
                            ->content(fn ($record) => $record ? number_format($record->exchange_rate_used, 6) . ' / XOF' : '-'),
                    ])
                    ->columns(2),

                Section::make('Statut et paiement')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'draft' => 'Brouillon',
                                'sent' => 'Envoyée',
                                'paid' => 'Payée',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Date d\'échéance')
                            ->required()
                            ->minDate(now()),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Payée le')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'paid'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Hébergeur')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('period')
                    ->label('Période')
                    ->sortable(['month', 'year']),

                Tables\Columns\TextColumn::make('total_bookings')
                    ->label('Réservations')
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('CA Total')
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'sent',
                        'success' => 'paid',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyée',
                        'paid' => 'Payée',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->is_overdue ? 'danger' : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Hébergeur')
                    ->relationship('user', 'name', fn ($query) => $query->where('type', 'host'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyée',
                        'paid' => 'Payée',
                    ]),

                Tables\Filters\Filter::make('period')
                    ->form([
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
                            ]),
                        Forms\Components\Select::make('year')
                            ->label('Année')
                            ->options(function () {
                                $years = [];
                                $startYear = Invoice::min('year') ?? now()->year - 1;
                                $endYear = now()->year;
                                for ($year = $startYear; $year <= $endYear; $year++) {
                                    $years[$year] = $year;
                                }
                                return $years;
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['month'],
                                fn (Builder $query, $month): Builder => $query->where('month', $month)
                            )
                            ->when(
                                $data['year'],
                                fn (Builder $query, $year): Builder => $query->where('year', $year)
                            );
                    }),

                Tables\Filters\Filter::make('overdue')
                    ->label('En retard')
                    ->query(fn (Builder $query): Builder => $query->overdue()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_details')
                    ->label('Voir détails')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (Invoice $record) => view('filament.resources.invoice.details', [
                        'invoice' => $record,
                        'items' => $record->items()->with('booking')->get(),
                    ]))
                    ->modalWidth('4xl'),

                Tables\Actions\Action::make('send')
                    ->label('Envoyer')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->action(fn (Invoice $record) => $record->markAsSent())
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => $record->status === 'draft'),

                Tables\Actions\Action::make('mark_paid')
                    ->label('Marquer payée')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Invoice $record) => $record->markAsPaid())
                    ->requiresConfirmation()
                    ->visible(fn (Invoice $record) => $record->status === 'sent'),

                Tables\Actions\Action::make('download_pdf')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Invoice $record) => response()->download($record->generatePdf()))
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('send')
                        ->label('Envoyer')
                        ->icon('heroicon-o-envelope')
                        ->action(fn ($records) => $records->each->markAsSent())
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('download')
                        ->label('Télécharger PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Logique pour télécharger plusieurs PDFs en ZIP
                            $this->notify('success', 'Téléchargement en cours...');
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                'user.name',
                'status',
                'year',
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'generate' => Pages\GenerateMonthlyInvoices::route('/generate'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'sent')->where('due_date', '<', now())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'danger' : 'gray';
    }
}
