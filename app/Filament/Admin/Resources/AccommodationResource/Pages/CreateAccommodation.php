<?php

namespace App\Filament\Admin\Resources\AccommodationResource\Pages;

use App\Filament\Admin\Resources\AccommodationResource;
use App\Models\Availability;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Notifications\Notification;

class CreateAccommodation extends CreateRecord
{
    use HasWizard;

    protected static string $resource = AccommodationResource::class;

    protected function getSteps(): array
    {
        return [
            \Filament\Forms\Components\Wizard\Step::make('Informations générales')
                ->description('Détails de base de l\'hébergement')
                ->icon('heroicon-o-building-office')
                ->schema([
                    \Filament\Forms\Components\Section::make()
                        ->schema([
                            \Filament\Forms\Components\Select::make('user_id')
                                ->label('Hébergeur')
                                ->relationship('user', 'name', fn ($query) => $query->where('type', 'host'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    \Filament\Forms\Components\TextInput::make('name')
                                        ->required(),
                                    \Filament\Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->required(),
                                    \Filament\Forms\Components\Hidden::make('type')
                                        ->default('host'),
                                ]),

                            \Filament\Forms\Components\Select::make('type')
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

                            \Filament\Forms\Components\TextInput::make('name')
                                ->label('Nom de l\'établissement')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, $get) {
                                    if (!$get('slug')) {
                                        $set('slug', \Illuminate\Support\Str::slug($state));
                                    }
                                }),

                            \Filament\Forms\Components\TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                        ])
                        ->columns(2),

                    \Filament\Forms\Components\Section::make('Description')
                        ->schema([
                            \Filament\Forms\Components\Tabs::make('Descriptions')
                                ->tabs([
                                    \Filament\Forms\Components\Tabs\Tab::make('Français')
                                        ->schema([
                                            \Filament\Forms\Components\Textarea::make('description.fr')
                                                ->label('Description')
                                                ->required()
                                                ->rows(4)
                                                ->maxLength(5000),
                                        ]),
                                    \Filament\Forms\Components\Tabs\Tab::make('English')
                                        ->schema([
                                            \Filament\Forms\Components\Textarea::make('description.en')
                                                ->label('Description')
                                                ->rows(4)
                                                ->maxLength(5000),
                                        ]),
                                ]),
                        ]),
                ]),

            \Filament\Forms\Components\Wizard\Step::make('Localisation')
                ->description('Adresse et coordonnées GPS')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    \Filament\Forms\Components\Section::make()
                        ->schema([
                            \Filament\Forms\Components\Select::make('country_id')
                                ->label('Pays')
                                ->options(fn () => \App\Models\Country::active()->ordered()->get()->pluck('name.fr', 'id'))
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (\Filament\Forms\Set $set) {
                                    $set('city_id', null);
                                    $set('district_id', null);
                                }),

                            \Filament\Forms\Components\Select::make('city_id')
                                ->label('Ville')
                                ->options(function (\Filament\Forms\Get $get) {
                                    $countryId = $get('country_id');
                                    if (!$countryId) {
                                        return [];
                                    }
                                    return \App\Models\City::where('country_id', $countryId)
                                        ->active()
                                        ->ordered()
                                        ->get()
                                        ->mapWithKeys(fn ($city) => [$city->id => $city->getName()]);
                                })
                                ->searchable()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn (\Filament\Forms\Set $set) => $set('district_id', null)),

                            \Filament\Forms\Components\Select::make('district_id')
                                ->label('Quartier')
                                ->options(function (\Filament\Forms\Get $get) {
                                    $cityId = $get('city_id');
                                    if (!$cityId) {
                                        return [];
                                    }
                                    return \App\Models\District::where('city_id', $cityId)
                                        ->active()
                                        ->ordered()
                                        ->get()
                                        ->mapWithKeys(fn ($district) => [$district->id => $district->getName()]);
                                })
                                ->searchable(),

                            \Filament\Forms\Components\TextInput::make('address')
                                ->label('Adresse complète')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            \Filament\Forms\Components\TextInput::make('latitude')
                                ->label('Latitude')
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90),

                            \Filament\Forms\Components\TextInput::make('longitude')
                                ->label('Longitude')
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180),
                        ])
                        ->columns(2),
                ]),

            \Filament\Forms\Components\Wizard\Step::make('Contact et horaires')
                ->description('Informations de contact et règles')
                ->icon('heroicon-o-phone')
                ->schema([
                    \Filament\Forms\Components\Section::make('Informations de contact')
                        ->schema([
                            \Filament\Forms\Components\TextInput::make('phone')
                                ->label('Téléphone')
                                ->tel()
                                ->maxLength(255),

                            \Filament\Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255),

                            \Filament\Forms\Components\TextInput::make('website')
                                ->label('Site web')
                                ->url()
                                ->maxLength(255),
                        ])
                        ->columns(3),

                    \Filament\Forms\Components\Section::make('Horaires et règles')
                        ->schema([
                            \Filament\Forms\Components\TimePicker::make('check_in_time')
                                ->label('Heure d\'arrivée')
                                ->default('14:00')
                                ->required(),

                            \Filament\Forms\Components\TimePicker::make('check_out_time')
                                ->label('Heure de départ')
                                ->default('12:00')
                                ->required(),

                            \Filament\Forms\Components\TextInput::make('min_stay_days')
                                ->label('Séjour minimum (jours)')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required(),

                            \Filament\Forms\Components\TextInput::make('max_stay_days')
                                ->label('Séjour maximum (jours)')
                                ->numeric()
                                ->minValue(1),
                        ])
                        ->columns(2),
                ]),

            \Filament\Forms\Components\Wizard\Step::make('Équipements et configuration')
                ->description('Finaliser la configuration')
                ->icon('heroicon-o-cog')
                ->schema([
                    \Filament\Forms\Components\Section::make('Équipements')
                        ->schema([
                            \Filament\Forms\Components\CheckboxList::make('amenities')
                                ->label('Équipements disponibles')
                                ->options(function () {
                                    return \App\Models\Amenity::forAccommodations()
                                        ->mapWithKeys(fn ($amenity) => [$amenity->icon => $amenity->getName()])
                                        ->toArray();
                                })
                                ->columns(3),
                        ]),

                    \Filament\Forms\Components\Section::make('Configuration')
                        ->schema([
                            \Filament\Forms\Components\Select::make('currency_id')
                                ->label('Devise')
                                ->relationship('currency', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            \Filament\Forms\Components\Select::make('status')
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
        ];
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Hébergement créé avec succès';
    }

    protected function afterCreate(): void
    {
        // Notifier l'hébergeur
        Notification::make()
            ->title('Nouvel hébergement créé')
            ->body("L'hébergement {$this->record->name} a été créé avec succès. Vous pouvez maintenant ajouter des chambres.")
            ->sendToDatabase($this->record->user);
    }
}
