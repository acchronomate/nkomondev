<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ClientResource\Pages;
use App\Filament\Admin\Resources\ClientResource\RelationManagers;
use App\Filament\Client\Resources\BookingResource;
use App\Models\User;
use Exception;
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

class ClientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Gestion des utilisateurs';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Client';

    protected static ?string $pluralModelLabel = 'Clients';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'client');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
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
                            ->default('client'),
                    ])
                    ->columns(),

                Section::make('Coordonnées')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
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
                    ->columns(),

                Section::make('Préférences')
                    ->schema([
                        Forms\Components\Select::make('locale')
                            ->label('Langue préférée')
                            ->options([
                                'fr' => 'Français',
                                'en' => 'English',
                            ])
                            ->default('fr')
                            ->required(),

                        Forms\Components\Select::make('preferred_currency_id')
                            ->label('Devise préférée')
                            ->relationship('preferredCurrency', 'name')
                            ->searchable()
                            ->preload()
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
                    ->columns(),

                Section::make('Historique client')
                    ->schema([
                        Placeholder::make('total_bookings')
                            ->label('Nombre de réservations')
                            ->content(fn ($record): string => $record ? $record->bookings()->count() : '0'),

                        Placeholder::make('completed_bookings')
                            ->label('Séjours effectués')
                            ->content(fn ($record): string => $record ? $record->bookings()->where('status', 'completed')->count() : '0'),

                        Placeholder::make('cancelled_bookings')
                            ->label('Réservations annulées')
                            ->content(fn ($record): string => $record ? $record->bookings()->where('status', 'cancelled')->count() : '0'),

                        Placeholder::make('reviews_count')
                            ->label('Avis laissés')
                            ->content(fn ($record): string => $record ? $record->reviews()->count() : '0'),

                        Placeholder::make('average_rating_given')
                            ->label('Note moyenne donnée')
                            ->content(fn ($record): string => $record ?
                                number_format($record->reviews()->avg('rating') ?? 0, 1) . '/5' : '-'),

                        Placeholder::make('last_booking')
                            ->label('Dernière réservation')
                            ->content(fn ($record): string => $record && $record->bookings()->latest()->first() ?
                                $record->bookings()->latest()->first()->created_at->format('d/m/Y') : 'Aucune'),
                    ])
                    ->columns(3)
                    ->visible(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
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
                    ->toggleable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Réservations')
                    ->counts('bookings')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('Avis')
                    ->counts('reviews')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('client_status')
                    ->badge()
                    ->label('Statut client')
                    ->getStateUsing(function ($record) {
                        $bookingsCount = $record->bookings()->count();
                        if ($bookingsCount === 0) {
                            return 'nouveau';
                        }
                        if ($bookingsCount === 1) {
                            return 'occasionnel';
                        }
                        if ($bookingsCount < 5) {
                            return 'régulier';
                        }
                        return 'fidèle';
                    })
                    ->colors([
                        'gray' => 'nouveau',
                        'warning' => 'occasionnel',
                        'primary' => 'régulier',
                        'success' => 'fidèle',
                    ]),

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

                Tables\Filters\Filter::make('has_bookings')
                    ->label('Avec réservations')
                    ->query(fn (Builder $query): Builder => $query->has('bookings')),

                Tables\Filters\SelectFilter::make('client_type')
                    ->label('Type de client')
                    ->options([
                        'new' => 'Nouveaux (0 réservation)',
                        'occasional' => 'Occasionnels (1 réservation)',
                        'regular' => 'Réguliers (2-4 réservations)',
                        'loyal' => 'Fidèles (5+ réservations)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match($data['value']) {
                            'new' => $query->doesntHave('bookings'),
                            'occasional' => $query->has('bookings', '=', 1),
                            'regular' => $query->has('bookings', '>=', 2)->has('bookings', '<=', 4),
                            'loyal' => $query->has('bookings', '>=', 5),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('verify')
                    ->label('Vérifier email')
                    ->icon('heroicon-o-check-badge')
                    ->action(fn (User $record) => $record->markEmailAsVerified())
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => !$record->hasVerifiedEmail()),

                Tables\Actions\Action::make('view_bookings')
                    ->label('Voir réservations')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (User $record): string => BookingResource::getUrl('index', ['client' => $record->id]))
                    ->visible(fn (User $record): bool => $record->bookings()->count() > 0),

                Tables\Actions\Action::make('convert_to_host')
                    ->label('Convertir en hébergeur')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn (User $record) => $record->update(['type' => 'host']))
                    ->requiresConfirmation()
                    ->modalHeading('Convertir en hébergeur')
                    ->modalDescription('Êtes-vous sûr de vouloir convertir ce client en hébergeur ? Il pourra alors créer et gérer des hébergements.')
                    ->modalSubmitActionLabel('Oui, convertir')
                    ->successNotificationTitle('Client converti en hébergeur'),
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

                    Tables\Actions\BulkAction::make('verify_emails')
                        ->label('Vérifier les emails')
                        ->icon('heroicon-o-check-badge')
                        ->action(fn ($records) => $records->each->markEmailAsVerified())
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

}
