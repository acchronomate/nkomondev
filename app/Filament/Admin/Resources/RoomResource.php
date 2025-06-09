<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\RoomResource\Pages;
use App\Filament\Admin\Resources\RoomResource\RelationManagers;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Accommodation;
use App\Models\Amenity;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Notifications\Notification;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Gestion des hébergements';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Chambre';

    protected static ?string $pluralModelLabel = 'Chambres';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de base')
                    ->schema([
                        Forms\Components\Select::make('accommodation_id')
                            ->label('Hébergement')
                            ->relationship('accommodation', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($livewire) => $livewire instanceof Pages\EditRoom),

                        Forms\Components\Select::make('room_type')
                            ->label('Type de chambre')
                            ->options([
                                'single' => 'Simple',
                                'double' => 'Double',
                                'twin' => 'Twin (2 lits simples)',
                                'triple' => 'Triple',
                                'suite' => 'Suite',
                                'family' => 'Familiale',
                                'dormitory' => 'Dortoir',
                            ])
                            ->required(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name.fr')
                                    ->label('Nom (Français)')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('name.en')
                                    ->label('Nom (English)')
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('Description')
                    ->schema([
                        Forms\Components\Tabs::make('Descriptions')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Français')
                                    ->schema([
                                        Forms\Components\Textarea::make('description.fr')
                                            ->label('Description')
                                            ->rows(3)
                                            ->maxLength(2000),
                                    ]),
                                Forms\Components\Tabs\Tab::make('English')
                                    ->schema([
                                        Forms\Components\Textarea::make('description.en')
                                            ->label('Description')
                                            ->rows(3)
                                            ->maxLength(2000),
                                    ]),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Capacité et tarification')
                    ->schema([
                        Forms\Components\TextInput::make('capacity_adults')
                            ->label('Capacité adultes')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->default(2),

                        Forms\Components\TextInput::make('capacity_children')
                            ->label('Capacité enfants')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\TextInput::make('base_price_per_night')
                            ->label('Prix de base par nuit')
                            ->numeric()
                            ->prefix(fn ($record) => $record?->accommodation?->currency?->symbol ?? 'FCFA')
                            ->minValue(0)
                            ->required(),

                        Forms\Components\TextInput::make('total_quantity')
                            ->label('Nombre total d\'unités')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->helperText('Nombre de chambres identiques de ce type'),
                    ])
                    ->columns(2),

                Section::make('Caractéristiques')
                    ->schema([
                        Forms\Components\TextInput::make('size_sqm')
                            ->label('Superficie (m²)')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('m²'),

                        Forms\Components\TextInput::make('bed_type')
                            ->label('Type de lit')
                            ->placeholder('Ex: 1 lit double ou 2 lits simples')
                            ->maxLength(255),

                        Forms\Components\CheckboxList::make('amenities')
                            ->label('Équipements')
                            ->options(function () {
                                return Amenity::forRooms()
                                    ->mapWithKeys(fn ($amenity) => [$amenity->icon => $amenity->getName()])
                                    ->toArray();
                            })
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('accommodation.name')
                    ->label('Hébergement')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->getStateUsing(fn ($record) => $record->getName())
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('room_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'single' => 'Simple',
                        'double' => 'Double',
                        'twin' => 'Twin',
                        'triple' => 'Triple',
                        'suite' => 'Suite',
                        'family' => 'Familiale',
                        'dormitory' => 'Dortoir',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacité')
                    ->getStateUsing(fn ($record) => $record->capacity_adults + $record->capacity_children . ' pers.')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('base_price_per_night')
                    ->label('Prix/nuit')
                    ->money(fn ($record) => $record->accommodation->currency->code)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('size_sqm')
                    ->label('Surface')
                    ->numeric()
                    ->suffix(' m²')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('current_availability')
                    ->label('Disponible')
                    ->getStateUsing(function (Model $record) {
                        $today = now()->format('Y-m-d');
                        return $record->getAvailableQuantityForDate($today) . '/' . $record->total_quantity;
                    })
                    ->badge()
                    ->color(fn ($state, Model $record) => $state === '0/' . $record->total_quantity ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('accommodation_id')
                    ->label('Hébergement')
                    ->relationship('accommodation', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('room_type')
                    ->label('Type')
                    ->options([
                        'single' => 'Simple',
                        'double' => 'Double',
                        'twin' => 'Twin',
                        'triple' => 'Triple',
                        'suite' => 'Suite',
                        'family' => 'Familiale',
                        'dormitory' => 'Dortoir',
                    ]),

                Tables\Filters\Filter::make('capacity')
                    ->form([
                        Forms\Components\TextInput::make('min_capacity')
                            ->label('Capacité minimale')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['min_capacity'],
                            fn (Builder $query, $value): Builder => $query->whereRaw('capacity_adults + capacity_children >= ?', [$value])
                        );
                    }),

                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('min_price')
                            ->label('Prix minimum')
                            ->numeric(),
                        Forms\Components\TextInput::make('max_price')
                            ->label('Prix maximum')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn (Builder $query, $value): Builder => $query->where('base_price_per_night', '>=', $value)
                            )
                            ->when(
                                $data['max_price'],
                                fn (Builder $query, $value): Builder => $query->where('base_price_per_night', '<=', $value)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manage_availability')
                    ->label('Gérer disponibilités')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn (Room $record): string => AvailabilityResource::getUrl('index', ['room' => $record->id]))
                    ->color('warning'),

                Tables\Actions\Action::make('duplicate')
                    ->label('Dupliquer')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Room $record) {
                        $newRoom = $record->replicate();
                        $newRoom->name = array_map(fn($name) => $name . ' (Copie)', $record->name);
                        $newRoom->save();

                        Notification::make()
                            ->success()
                            ->title('Chambre dupliquée')
                            ->body('La chambre a été dupliquée avec succès.')
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('update_prices')
                        ->label('Mettre à jour les prix')
                        ->icon('heroicon-o-currency-dollar')
                        ->form([
                            Forms\Components\Select::make('operation')
                                ->label('Opération')
                                ->options([
                                    'increase_percent' => 'Augmenter de %',
                                    'decrease_percent' => 'Diminuer de %',
                                    'increase_amount' => 'Augmenter de montant',
                                    'decrease_amount' => 'Diminuer de montant',
                                    'set_price' => 'Définir le prix',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('value')
                                ->label('Valeur')
                                ->numeric()
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $newPrice = match($data['operation']) {
                                    'increase_percent' => $record->base_price_per_night * (1 + $data['value'] / 100),
                                    'decrease_percent' => $record->base_price_per_night * (1 - $data['value'] / 100),
                                    'increase_amount' => $record->base_price_per_night + $data['value'],
                                    'decrease_amount' => $record->base_price_per_night - $data['value'],
                                    'set_price' => $data['value'],
                                };

                                $record->update(['base_price_per_night' => max(0, $newPrice)]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'view' => Pages\ViewRoom::route('/{record}'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['accommodation.currency']);
    }
}
