<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CityResource\Pages;
use App\Filament\Admin\Resources\CityResource\RelationManagers;
use App\Models\City;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Country;
use Filament\Forms\Components\Section;
use Illuminate\Support\Str;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Gestion des localisations';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Ville';

    protected static ?string $pluralModelLabel = 'Villes';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de la ville')
                    ->schema([
                        Forms\Components\Select::make('country_id')
                            ->label('Pays')
                            ->relationship('country', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->getName())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('code')
                                    ->label('Code ISO')
                                    ->required()
                                    ->maxLength(2),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name.fr')
                                            ->label('Nom (Français)')
                                            ->required(),
                                        Forms\Components\TextInput::make('name.en')
                                            ->label('Nom (English)')
                                            ->required(),
                                    ]),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.fr')
                                    ->label('Nom (Français)')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        if (!$get('slug')) {
                                            $set('slug', Str::slug($state));
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

                        Forms\Components\TextInput::make('postal_code')
                            ->label('Code postal')
                            ->maxLength(20),
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
                    ->columns(2),

                Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Toggle::make('is_popular')
                            ->label('Ville populaire')
                            ->helperText('Les villes populaires sont mises en avant sur la page d\'accueil'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Les villes inactives ne sont pas affichées dans les formulaires'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('country.name')
                    ->label('Pays')
                    ->getStateUsing(fn ($record) => $record->country->getName())
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name.fr')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('districts_count')
                    ->label('Quartiers')
                    ->counts('districts')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('accommodations_count')
                    ->label('Hébergements')
                    ->counts('accommodations')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_popular')
                    ->label('Populaire')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('country_id')
                    ->label('Pays')
                    ->options(function () {
                        return Country::active()
                            ->ordered()
                            ->get()
                            ->mapWithKeys(fn ($country) => [$country->id => $country->getName()]);
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_popular')
                    ->label('Popularité')
                    ->placeholder('Toutes')
                    ->trueLabel('Populaires')
                    ->falseLabel('Normales'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Statut')
                    ->placeholder('Toutes')
                    ->trueLabel('Actives')
                    ->falseLabel('Inactives'),

                Tables\Filters\Filter::make('has_accommodations')
                    ->label('Avec hébergements')
                    ->query(fn ($query) => $query->has('accommodations')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_districts')
                    ->label('Voir quartiers')
                    ->icon('heroicon-o-map')
                    ->url(fn (City $record): string => DistrictResource::getUrl('index', ['city' => $record->id]))
                    ->visible(fn (City $record): bool => $record->districts()->count() > 0),

                Tables\Actions\Action::make('toggle_popular')
                    ->label(fn (City $record): string => $record->is_popular ? 'Retirer des populaires' : 'Marquer populaire')
                    ->icon('heroicon-o-star')
                    ->color(fn (City $record): string => $record->is_popular ? 'gray' : 'warning')
                    ->action(fn (City $record) => $record->update(['is_popular' => !$record->is_popular])),
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

                    Tables\Actions\BulkAction::make('mark_popular')
                        ->label('Marquer populaires')
                        ->icon('heroicon-o-star')
                        ->action(fn ($records) => $records->each->update(['is_popular' => true]))
                        ->color('warning')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('name.fr')
            ->groups([
                'country.name',
                'is_popular',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'view' => Pages\ViewCity::route('/{record}'),
            'edit' => Pages\EditCity::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }
}
