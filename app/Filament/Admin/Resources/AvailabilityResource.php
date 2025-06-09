<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AvailabilityResource\Pages;
use App\Filament\Admin\Resources\AvailabilityResource\RelationManagers;
use App\Models\Availability;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Room;
use App\Models\Accommodation;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Carbon\Carbon;

class AvailabilityResource extends Resource
{
    protected static ?string $model = Availability::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Gestion des réservations';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Disponibilité';

    protected static ?string $pluralModelLabel = 'Disponibilités';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Chambre et période')
                    ->schema([
                        Forms\Components\Select::make('accommodation_id')
                            ->label('Hébergement')
                            ->options(fn() => Accommodation::active()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => $set('room_id', null))
                            ->dehydrated(false),

                        Forms\Components\Select::make('room_id')
                            ->label('Chambre')
                            ->options(function (Get $get) {
                                $accommodationId = $get('accommodation_id');
                                if (!$accommodationId) {
                                    return Room::all()->mapWithKeys(fn ($room) => [
                                        $room->id => $room->accommodation->name . ' - ' . $room->getName()
                                    ]);
                                }
                                return Room::where('accommodation_id', $accommodationId)
                                    ->get()
                                    ->mapWithKeys(fn ($room) => [$room->id => $room->getName()]);
                            })
                            ->searchable()
                            ->required()
                            ->reactive(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->minDate(now())
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, Get $get) {
                                return $rule->where('room_id', $get('room_id'));
                            }),
                    ])
                    ->columns(3),

                Section::make('Disponibilité et tarif')
                    ->schema([
                        Forms\Components\TextInput::make('available_quantity')
                            ->label('Quantité disponible')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $room = Room::find($get('room_id'));
                                if ($room && $state > $room->total_quantity) {
                                    $set('available_quantity', $room->total_quantity);
                                }
                            }),

                        Forms\Components\TextInput::make('price_override')
                            ->label('Prix spécial')
                            ->numeric()
                            ->minValue(0)
                            ->prefix(fn (Get $get) => Room::find($get('room_id'))?->accommodation?->currency?->symbol ?? 'FCFA')
                            ->helperText('Laisser vide pour utiliser le prix de base'),

                        Forms\Components\Toggle::make('is_blocked')
                            ->label('Bloquer cette date')
                            ->helperText('Empêche toute réservation pour cette date'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room.accommodation.name')
                    ->label('Hébergement')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('room.name')
                    ->label('Chambre')
                    ->getStateUsing(fn ($record) => $record->room?->getName())
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->date->isPast() ? 'gray' : 'primary'),

                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Disponible')
                    ->numeric()
                    ->suffix(fn ($record) => '/' . $record->room->total_quantity)
                    ->color(fn ($record) => match(true) {
                        $record->available_quantity === 0 => 'danger',
                        $record->available_quantity < $record->room->total_quantity => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('price')
                    ->label('Prix')
                    ->getStateUsing(fn ($record) => $record->room->accommodation->currency->format(
                        $record->price_override ?? $record->room->base_price_per_night
                    ))
                    ->badge()
                    ->color(fn ($record) => $record->price_override ? 'warning' : 'gray'),

                Tables\Columns\IconColumn::make('is_blocked')
                    ->label('Bloquée')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Réservations')
                    ->getStateUsing(function ($record) {
                        return $record->room->bookings()
                            ->where('check_in', '<=', $record->date)
                            ->where('check_out', '>', $record->date)
                            ->whereIn('status', ['confirmed', 'pending'])
                            ->count();
                    })
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_id')
                    ->label('Chambre')
                    ->relationship('room', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->accommodation->name . ' - ' . $record->getName())
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('dates')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('is_blocked')
                    ->label('Statut')
                    ->placeholder('Toutes')
                    ->trueLabel('Bloquées')
                    ->falseLabel('Disponibles'),

                Tables\Filters\Filter::make('availability')
                    ->label('Disponibilité')
                    ->form([
                        Forms\Components\Radio::make('availability_status')
                            ->options([
                                'available' => 'Disponibles',
                                'partially' => 'Partiellement disponibles',
                                'full' => 'Complètes',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match($data['availability_status'] ?? null) {
                            'available' => $query->where('available_quantity', '>', 0)->where('is_blocked', false),
                            'partially' => $query->whereColumn('available_quantity', '<', 'rooms.total_quantity')
                                ->where('available_quantity', '>', 0),
                            'full' => $query->where('available_quantity', 0),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_block')
                    ->label(fn (Availability $record) => $record->is_blocked ? 'Débloquer' : 'Bloquer')
                    ->icon(fn (Availability $record) => $record->is_blocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (Availability $record) => $record->is_blocked ? 'success' : 'danger')
                    ->action(fn (Availability $record) => $record->update(['is_blocked' => !$record->is_blocked]))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('update_availability')
                        ->label('Mettre à jour la disponibilité')
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            Forms\Components\Select::make('action')
                                ->label('Action')
                                ->options([
                                    'set_quantity' => 'Définir la quantité',
                                    'increase_quantity' => 'Augmenter la quantité',
                                    'decrease_quantity' => 'Diminuer la quantité',
                                    'block' => 'Bloquer',
                                    'unblock' => 'Débloquer',
                                ])
                                ->required()
                                ->reactive(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantité')
                                ->numeric()
                                ->minValue(0)
                                ->visible(fn (Get $get) => in_array($get('action'), ['set_quantity', 'increase_quantity', 'decrease_quantity'])),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                switch ($data['action']) {
                                    case 'set_quantity':
                                        $record->update(['available_quantity' => min($data['quantity'], $record->room->total_quantity)]);
                                        break;
                                    case 'increase_quantity':
                                        $record->increaseQuantity($data['quantity']);
                                        break;
                                    case 'decrease_quantity':
                                        $record->decreaseQuantity($data['quantity']);
                                        break;
                                    case 'block':
                                        $record->update(['is_blocked' => true]);
                                        break;
                                    case 'unblock':
                                        $record->update(['is_blocked' => false]);
                                        break;
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('update_prices')
                        ->label('Mettre à jour les prix')
                        ->icon('heroicon-o-currency-dollar')
                        ->form([
                            Forms\Components\TextInput::make('price')
                                ->label('Prix spécial')
                                ->numeric()
                                ->minValue(0)
                                ->helperText('Laisser vide pour supprimer le prix spécial'),
                        ])
                        ->action(fn ($records, array $data) => $records->each->update(['price_override' => $data['price']]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvailabilities::route('/'),
            'create' => Pages\CreateAvailability::route('/create'),
            'view' => Pages\ViewAvailability::route('/{record}'),
            'edit' => Pages\EditAvailability::route('/{record}/edit'),
            'calendar-view' => Pages\CalendarView::route('/calendar'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['room.accommodation.currency'])
            ->join('rooms', 'availabilities.room_id', '=', 'rooms.id');
    }
}
