<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CurrencyResource\Pages;
use App\Filament\Admin\Resources\CurrencyResource\RelationManagers;
use App\Models\Currency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Cache;
use App\Models\ExchangeRateHistory;
use Filament\Notifications\Notification;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Devise';

    protected static ?string $pluralModelLabel = 'Devises';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de la devise')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code ISO')
                            ->required()
                            ->maxLength(3)
                            ->minLength(3)
                            ->unique(ignoreRecord: true)
                            ->placeholder('XOF')
                            ->helperText('Code ISO 4217 (3 lettres)')
                            ->disabled(fn ($record) => $record?->is_default),

                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Franc CFA'),

                        Forms\Components\TextInput::make('symbol')
                            ->label('Symbole')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('FCFA'),

                        Forms\Components\TextInput::make('decimal_places')
                            ->label('Nombre de décimales')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(4)
                            ->default(2),
                    ])
                    ->columns(2),

                Section::make('Taux de change')
                    ->schema([
                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Taux de change (par rapport à XOF)')
                            ->numeric()
                            ->required()
                            ->minValue(0.000001)
                            ->step(0.000001)
                            ->default(1)
                            ->helperText('1 XOF = ? de cette devise')
                            ->disabled(fn ($record) => $record?->is_default)
                            ->afterStateUpdated(function ($state, $old, $record) {
                                if ($record && $state !== $old) {
                                    // Enregistrer dans l'historique
                                    ExchangeRateHistory::create([
                                        'currency_id' => $record->id,
                                        'rate' => $state,
                                        'changed_by' => auth()->id(),
                                    ]);
                                }
                            }),

                        Forms\Components\Placeholder::make('rate_info')
                            ->label('Information')
                            ->content(fn ($record) => $record?->is_default
                                ? 'Devise de base - Le taux est toujours 1'
                                : 'Dernière mise à jour : ' . ($record?->updated_at?->format('d/m/Y H:i') ?? 'Jamais')),
                    ]),

                Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->disabled(fn ($record) => $record?->is_default)
                            ->helperText('Les devises inactives ne peuvent pas être utilisées'),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Devise par défaut')
                            ->disabled()
                            ->helperText('La devise par défaut ne peut pas être modifiée'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->is_default ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('symbol')
                    ->label('Symbole')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('exchange_rate')
                    ->label('Taux de change')
                    ->numeric(6)
                    ->suffix(' / XOF')
                    ->sortable(),

                Tables\Columns\TextColumn::make('decimal_places')
                    ->label('Décimales')
                    ->numeric()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Par défaut')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('')
                    ->trueColor('warning'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mise à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Toutes')
                    ->trueLabel('Actives')
                    ->falseLabel('Inactives'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('update_rate')
                    ->label('Mettre à jour le taux')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\TextInput::make('new_rate')
                            ->label('Nouveau taux')
                            ->numeric()
                            ->required()
                            ->minValue(0.000001)
                            ->step(0.000001)
                            ->helperText('1 XOF = ? de cette devise'),
                    ])
                    ->action(function (Currency $record, array $data) {
                        $record->update(['exchange_rate' => $data['new_rate']]);

                        ExchangeRateHistory::create([
                            'currency_id' => $record->id,
                            'rate' => $data['new_rate'],
                            'changed_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Taux mis à jour')
                            ->body("Le taux de change pour {$record->name} a été mis à jour.")
                            ->send();
                    })
                    ->visible(fn (Currency $record) => !$record->is_default),

                Tables\Actions\Action::make('view_history')
                    ->label('Historique')
                    ->icon('heroicon-o-clock')
                    ->modalContent(fn (Currency $record) => view('filament.resources.currency.exchange-rate-history', [
                        'currency' => $record,
                        'history' => $record->exchangeRateHistory()->latest()->limit(20)->get(),
                    ]))
                    ->modalWidth('lg'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activer')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Désactiver')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each(function ($record) {
                            if (!$record->is_default) {
                                $record->update(['is_active' => false]);
                            }
                        }))
                        ->color('danger')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('code');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'view' => Pages\ViewCurrency::route('/{record}'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
