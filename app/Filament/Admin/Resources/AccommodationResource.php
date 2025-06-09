<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AccommodationResource\Pages;
use App\Filament\Admin\Resources\AccommodationResource\RelationManagers;
use App\Models\Accommodation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Country;
use App\Models\City;
use App\Models\District;
use App\Models\Amenity;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Str;

class AccommodationResource extends Resource
{
    protected static ?string $model = Accommodation::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Gestion des hébergements';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Hébergement';

    protected static ?string $pluralModelLabel = 'Hébergements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Hébergement')
                    ->tabs([
                        Tabs\Tab::make('Informations générales')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('Hébergeur')
                                            ->relationship('user', 'name', fn ($query) => $query->where('type', 'host'))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required(),
                                                Forms\Components\TextInput::make('email')
                                                    ->email()
                                                    ->required(),
                                                Forms\Components\Hidden::make('type')
                                                    ->default('host'),
                                            ]),

                                        Forms\Components\Select::make('type')
                                            ->label('Type d\'hébergement')
                                            ->options([
                                                'hotel' => 'Hôtel',
                                                'motel' => 'Motel',
                                                'appart_hotel' => 'Appart\'Hôtel',
                                                'village_vacances' => 'Village de vacances',
                                                'bungalow' => 'Bungalow',
                                                'maison_hotes' => 'Maison d\'hôtes',
                                            ])
                                            ->required(),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Nom de l\'établissement')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set, $get) {
                                                if (!$get('slug')) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            }),

                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Section::make('Description')
                                    ->schema([
                                        Forms\Components\Tabs::make('Descriptions')
                                            ->tabs([
                                                Forms\Components\Tabs\Tab::make('Français')
                                                    ->schema([
                                                        Forms\Components\Textarea::make('description.fr')
                                                            ->label('Description')
                                                            ->required()
                                                            ->rows(4)
                                                            ->maxLength(5000),
                                                    ]),
                                                Forms\Components\Tabs\Tab::make('English')
                                                    ->schema([
                                                        Forms\Components\Textarea::make('description.en')
                                                            ->label('Description')
                                                            ->rows(4)
                                                            ->maxLength(5000),
                                                    ]),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Localisation')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Forms\Components\Select::make('country_id')
                                            ->label('Pays')
                                            ->options(fn() => Country::active()
                                                ->ordered()
                                                ->get()
                                                ->mapWithKeys(fn ($country) => [$country->id => $country->getName()]))
                                            ->searchable()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('city_id', null);
                                                $set('district_id', null);
                                            }),

                                        Forms\Components\Select::make('city_id')
                                            ->label('Ville')
                                            ->options(function (Get $get) {
                                                $countryId = $get('country_id');
                                                if (!$countryId) {
                                                    return [];
                                                }
                                                return City::where('country_id', $countryId)
                                                    ->active()
                                                    ->ordered()
                                                    ->get()
                                                    ->mapWithKeys(fn ($city) => [$city->id => $city->getName()]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn (Set $set) => $set('district_id', null)),

                                        Forms\Components\Select::make('district_id')
                                            ->label('Quartier')
                                            ->options(function (Get $get) {
                                                $cityId = $get('city_id');
                                                if (!$cityId) {
                                                    return [];
                                                }
                                                return District::where('city_id', $cityId)
                                                    ->active()
                                                    ->ordered()
                                                    ->get()
                                                    ->mapWithKeys(fn ($district) => [$district->id => $district->getName()]);
                                            })
                                            ->searchable(),

                                        Forms\Components\TextInput::make('address')
                                            ->label('Adresse complète')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('latitude')
                                            ->label('Latitude')
                                            ->numeric()
                                            ->minValue(-90)
                                            ->maxValue(90),

                                        Forms\Components\TextInput::make('longitude')
                                            ->label('Longitude')
                                            ->numeric()
                                            ->minValue(-180)
                                            ->maxValue(180),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Contact & Horaires')
                            ->schema([
                                Section::make('Informations de contact')
                                    ->schema([
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Téléphone')
                                            ->tel()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('website')
                                            ->label('Site web')
                                            ->url()
                                            ->maxLength(255),
                                    ])
                                    ->columns(3),

                                Section::make('Horaires et règles')
                                    ->schema([
                                        Forms\Components\TimePicker::make('check_in_time')
                                            ->label('Heure d\'arrivée')
                                            ->default('14:00')
                                            ->required(),

                                        Forms\Components\TimePicker::make('check_out_time')
                                            ->label('Heure de départ')
                                            ->default('12:00')
                                            ->required(),

                                        Forms\Components\TextInput::make('min_stay_days')
                                            ->label('Séjour minimum (jours)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(1)
                                            ->required(),

                                        Forms\Components\TextInput::make('max_stay_days')
                                            ->label('Séjour maximum (jours)')
                                            ->numeric()
                                            ->minValue(1),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Équipements & Configuration')
                            ->schema([
                                Section::make('Équipements')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('amenities')
                                            ->label('Équipements disponibles')
                                            ->options(function () {
                                                return Amenity::forAccommodations()
                                                    ->mapWithKeys(fn ($amenity) => [$amenity->icon => $amenity->getName()])
                                                    ->toArray();
                                            })
                                            ->columns(3),
                                    ]),

                                Section::make('Configuration')
                                    ->schema([
                                        Forms\Components\Select::make('currency_id')
                                            ->label('Devise')
                                            ->relationship('currency', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Forms\Components\Select::make('status')
                                            ->label('Statut')
                                            ->options([
                                                'active' => 'Actif',
                                                'inactive' => 'Inactif',
                                                'suspended' => 'Suspendu',
                                            ])
                                            ->default('active')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Hébergeur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'hotel' => 'Hôtel',
                        'motel' => 'Motel',
                        'appart_hotel' => 'Appart\'Hôtel',
                        'village_vacances' => 'Village vacances',
                        'bungalow' => 'Bungalow',
                        'maison_hotes' => 'Maison d\'hôtes',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Ville')
                    ->getStateUsing(fn ($record) => $record->city?->getName())
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Chambres')
                    ->counts('rooms')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('min_price')
                    ->label('Prix min')
                    ->money(fn ($record) => $record->currency->code)
                    ->getStateUsing(fn ($record) => $record->getMinPriceAttribute()),

                Tables\Columns\TextColumn::make('rating_average')
                    ->label('Note')
                    ->numeric(1)
                    ->suffix('/5')
                    ->color(fn ($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        $state > 0 => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Statut')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'suspended',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                        'suspended' => 'Suspendu',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
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

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'hotel' => 'Hôtel',
                        'motel' => 'Motel',
                        'appart_hotel' => 'Appart\'Hôtel',
                        'village_vacances' => 'Village vacances',
                        'bungalow' => 'Bungalow',
                        'maison_hotes' => 'Maison d\'hôtes',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                        'suspended' => 'Suspendu',
                    ]),

                Tables\Filters\Filter::make('has_rooms')
                    ->label('Avec chambres')
                    ->query(fn ($query) => $query->has('rooms')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_rooms')
                    ->label('Gérer chambres')
                    ->icon('heroicon-o-home')
                    ->url(fn (Accommodation $record): string => RoomResource::getUrl('index', ['accommodation' => $record->id])),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Accommodation $record): string => $record->status === 'active' ? 'Désactiver' : 'Activer')
                    ->icon(fn (Accommodation $record): string => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Accommodation $record): string => $record->status === 'active' ? 'danger' : 'success')
                    ->action(fn (Accommodation $record) => $record->update([
                        'status' => $record->status === 'active' ? 'inactive' : 'active'
                    ]))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccommodations::route('/'),
            'create' => Pages\CreateAccommodation::route('/create'),
            'view' => Pages\ViewAccommodation::route('/{record}'),
            'edit' => Pages\EditAccommodation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }
}
