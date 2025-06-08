<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DistrictResource\Pages;
use App\Filament\Admin\Resources\DistrictResource\RelationManagers;
use App\Models\District;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\City;
use App\Models\Country;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Illuminate\Support\Str;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Gestion des localisations';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Quartier';

    protected static ?string $pluralModelLabel = 'Quartiers';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations du quartier')
                    ->schema([
                        Forms\Components\Select::make('country_id')
                            ->label('Pays')
                            ->options(fn () => Country::active()
                                ->whereHas('cities')
                                ->ordered()
                                ->get()
                                ->mapWithKeys(fn ($country) => [$country->id => $country->getName()]))
                            ->getOptionLabelUsing(fn ($value): ?string => Country::find($value)?->getName())
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('city_id', null))
                            ->dehydrated(false),

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
                            ->getOptionLabelUsing(fn ($value): ?string => City::find($value)?->getName())
                            ->searchable()
                            ->required()
                            ->reactive(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.fr')
                                    ->label('Nom (Français)')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        if (!$get('slug') && $get('city_id')) {
                                            $city = City::find($get('city_id'));
                                            $slug = Str::slug($state . '-' . $city?->getName());
                                            $set('slug', $slug);
                                        }
                                    }),

                                Forms\Components\TextInput::make('name.en')
                                    ->label('Nom (English)')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('URL-friendly version du nom'),
                    ]),

                Section::make('Localisation GPS')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90)
                            ->step(0.00000001)
                            ->helperText('Ex: 6.3654'),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180)
                            ->step(0.00000001)
                            ->helperText('Ex: 2.4183'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->helperText('Les quartiers inactifs ne sont pas affichés dans les formulaires'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name.fr')
                    ->label('Quartier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Ville')
                    ->getStateUsing(fn ($record) => $record->city->getName())
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('city.country.name')
                    ->label('Pays')
                    ->getStateUsing(fn ($record) => $record->city->country->getName())
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('accommodations_count')
                    ->label('Hébergements')
                    ->counts('accommodations')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country')
                    ->label('Pays')
                    ->options(
                        Country::active()
                            ->whereHas('cities.districts')
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($country) => [$country->id => $country->getName()])
                    )
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            $query->whereHas('city.country', fn ($q) => $q->where('id', $data['value']));
                        }
                    }),

                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Ville')
                    ->options(function () {
                        return City::active()
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($city) => [$city->id => $city->getName()]);
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs'),

                Tables\Filters\Filter::make('has_accommodations')
                    ->label('Avec hébergements')
                    ->query(fn ($query) => $query->has('accommodations')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_accommodations')
                    ->label('Voir hébergements')
                    ->icon('heroicon-o-building-office')
                    ->url(fn (District $record): string => AccommodationResource::getUrl('index', ['district' => $record->id]))
                    ->visible(fn (District $record): bool => $record->accommodations()->count() > 0),

                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (District $record): string => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn (District $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (District $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(fn (District $record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),
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
            ->defaultSort('name')
            ->groups([
                'city.country.name',
                'city.name',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'view' => Pages\ViewDistrict::route('/{record}'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['city.country']);
    }
}
