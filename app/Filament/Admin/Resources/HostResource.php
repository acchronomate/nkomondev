<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\HostResource\Pages;
use App\Filament\Admin\Resources\HostResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Currency;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;

class HostResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Gestion des utilisateurs';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Hébergeur';

    protected static ?string $pluralModelLabel = 'Hébergeurs';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'host');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de l\'hébergeur')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de l\'établissement / Raison sociale')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email professionnel')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        Forms\Components\Hidden::make('type')
                            ->default('host'),
                    ])
                    ->columns(2),

                Section::make('Coordonnées professionnelles')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('country_id')
                            ->label('Pays')
                            ->relationship('country', 'name->fr')
                            ->searchable()
                            ->preload()
                            ->live(),

                        Forms\Components\Select::make('city_id')
                            ->label('Ville')
                            ->relationship(
                                'city',
                                'name->fr',
                                function (Builder $query, $get) {
                                    $countryId = $get('country_id');
                                    if ($countryId) {
                                        $query->where('country_id', $countryId);
                                    }
                                }
                            )
                            ->searchable()
                            ->preload()
                            ->reactive(),
                    ])
                    ->columns(2),

                Section::make('Configuration')
                    ->schema([
                        Forms\Components\Select::make('locale')
                            ->label('Langue')
                            ->options([
                                'fr' => 'Français',
                                'en' => 'English',
                            ])
                            ->default('fr')
                            ->required(),

                        Forms\Components\Select::make('preferred_currency_id')
                            ->label('Devise de facturation')
                            ->relationship('preferredCurrency', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => Currency::where('is_default', true)->first()?->id),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Compte actif')
                            ->default(true)
                            ->helperText('Un compte inactif ne peut plus se connecter'),

                        Forms\Components\Toggle::make('email_verified')
                            ->label('Email vérifié')
                            ->default(false)
                            ->dehydrated(false)
                            ->afterStateHydrated(fn ($component, $state, $record) =>
                            $component->state($record?->hasVerifiedEmail() ?? false)
                            ),
                    ])
                    ->columns(2),

                Section::make('Statistiques')
                    ->schema([
                        Placeholder::make('accommodations_count')
                            ->label('Nombre d\'hébergements')
                            ->content(fn ($record): string => $record ? $record->accommodations()->count() : '0'),

                        Placeholder::make('active_accommodations')
                            ->label('Hébergements actifs')
                            ->content(fn ($record): string => $record ? $record->accommodations()->where('status', 'active')->count() : '0'),

                        Placeholder::make('total_bookings')
                            ->label('Total réservations')
                            ->content(fn ($record): string => $record ? $record->accommodations()->withCount('bookings')->get()->sum('bookings_count') : '0'),

                        Placeholder::make('total_revenue')
                            ->label('Chiffre d\'affaires total')
                            ->content(fn ($record): string => $record ?
                                number_format($record->invoices()->sum('total_revenue'), 0, ',', ' ') . ' FCFA' : '0 FCFA'),
                    ])
                    ->columns(2)
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom établissement')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('accommodations_count')
                    ->label('Hébergements')
                    ->counts('accommodations')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('active_bookings_count')
                    ->label('Réservations actives')
                    ->getStateUsing(function ($record) {
                        return $record->accommodations()
                            ->join('rooms', 'accommodations.id', '=', 'rooms.accommodation_id')
                            ->join('bookings', 'rooms.id', '=', 'bookings.room_id')
                            ->whereIn('bookings.status', ['pending', 'confirmed'])
                            ->count();
                    })
                    ->badge()
                    ->color('warning'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email vérifié')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Dernière connexion')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_login_ip')
                    ->label('IP de dernière connexion')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email vérifié')
                    ->placeholder('Tous')
                    ->trueLabel('Vérifié')
                    ->falseLabel('Non vérifié')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query) => $query->whereNull('email_verified_at'),
                    ),

                Tables\Filters\Filter::make('has_accommodations')
                    ->label('Avec hébergements')
                    ->query(fn (Builder $query): Builder => $query->has('accommodations')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify')
                    ->label('Vérifier email')
                    ->icon('heroicon-o-check-badge')
                    ->action(fn (User $record) => $record->markEmailAsVerified())
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => !$record->hasVerifiedEmail()),

                Tables\Actions\Action::make('view_accommodations')
                    ->label('Voir hébergements')
                    ->icon('heroicon-o-building-office')
                    ->url(fn (User $record): string => AccommodationResource::getUrl('index', ['host' => $record->id]))
                    ->visible(fn (User $record): bool => $record->accommodations()->count() > 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activer')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Désactiver')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->color('danger')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListHosts::route('/'),
            'create' => Pages\CreateHost::route('/create'),
            'view' => Pages\ViewHost::route('/{record}'),
            'edit' => Pages\EditHost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->where('is_active', true)->count();
    }
}
