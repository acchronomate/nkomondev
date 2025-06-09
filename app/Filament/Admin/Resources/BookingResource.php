<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BookingResource\Pages;
use App\Filament\Admin\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\User;
use App\Models\Room;
use App\Models\Accommodation;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Carbon\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Gestion des réservations';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Réservation';

    protected static ?string $pluralModelLabel = 'Réservations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de réservation')
                    ->schema([
                        Forms\Components\TextInput::make('booking_number')
                            ->label('Numéro de réservation')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Select::make('user_id')
                            ->label('Client')
                            ->relationship('user', 'name', fn ($query) => $query->where('type', 'client'))
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
                                    ->default('client'),
                            ])
                            ->disabled(fn ($operation) => $operation === 'edit'),

                        Forms\Components\Select::make('accommodation_id')
                            ->label('Hébergement')
                            ->options(fn() => Accommodation::active()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (Set $set) => $set('room_id', null))
                            ->disabled(fn ($operation) => $operation === 'edit')
                            ->dehydrated(false),

                        Forms\Components\Select::make('room_id')
                            ->label('Chambre')
                            ->options(function (Get $get) {
                                $accommodationId = $get('accommodation_id');
                                if (!$accommodationId) {
                                    return [];
                                }
                                return Room::where('accommodation_id', $accommodationId)
                                    ->get()
                                    ->mapWithKeys(fn ($room) => [
                                        $room->id => $room->getName() . ' - ' . $room->accommodation->currency->format($room->base_price_per_night)
                                    ]);
                            })
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->disabled(fn ($operation) => $operation === 'edit'),
                    ])
                    ->columns(2),

                Section::make('Dates et invités')
                    ->schema([
                        Forms\Components\DatePicker::make('check_in')
                            ->label('Date d\'arrivée')
                            ->required()
                            ->minDate(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                $checkOut = $get('check_out');
                                if ($checkOut && Carbon::parse($state)->gte(Carbon::parse($checkOut))) {
                                    $set('check_out', null);
                                }
                            }),

                        Forms\Components\DatePicker::make('check_out')
                            ->label('Date de départ')
                            ->required()
                            ->minDate(fn (Get $get) => $get('check_in') ? Carbon::parse($get('check_in'))->addDay() : now()->addDay())
                            ->reactive(),

                        Forms\Components\TextInput::make('guests_adults')
                            ->label('Adultes')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->default(1),

                        Forms\Components\TextInput::make('guests_children')
                            ->label('Enfants')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Informations de contact')
                    ->schema([
                        Forms\Components\TextInput::make('guest_name')
                            ->label('Nom de l\'invité')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('guest_email')
                            ->label('Email de l\'invité')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('guest_phone')
                            ->label('Téléphone de l\'invité')
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('special_requests')
                            ->label('Demandes spéciales')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->columns(2),

                Section::make('Tarification')
                    ->schema([
                        Forms\Components\Placeholder::make('room_price_display')
                            ->label('Prix par nuit')
                            ->content(fn ($record) => $record ? $record->currency->format($record->room_price) : '-'),

                        Forms\Components\Placeholder::make('total_nights_display')
                            ->label('Nombre de nuits')
                            ->content(fn ($record) => $record ? $record->total_nights : '-'),

                        Forms\Components\Placeholder::make('subtotal_display')
                            ->label('Sous-total')
                            ->content(fn ($record) => $record ? $record->currency->format($record->subtotal) : '-'),

                        Forms\Components\Placeholder::make('commission_display')
                            ->label('Commission (5%)')
                            ->content(fn ($record) => $record ? $record->currency->format($record->commission_amount) : '-'),

                        Forms\Components\Placeholder::make('total_display')
                            ->label('Total')
                            ->content(fn ($record) => $record ? $record->currency->format($record->total_amount) : '-'),
                    ])
                    ->columns(2)
                    ->visible(fn ($operation) => $operation === 'edit'),

                Section::make('Statut')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'confirmed' => 'Confirmée',
                                'cancelled' => 'Annulée',
                                'completed' => 'Terminée',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Raison d\'annulation')
                            ->rows(2)
                            ->visible(fn (Get $get) => $get('status') === 'cancelled'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_number')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('room.accommodation.name')
                    ->label('Hébergement')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('room.name')
                    ->label('Chambre')
                    ->getStateUsing(fn ($record) => $record->room?->getName())
                    ->limit(20),

                Tables\Columns\TextColumn::make('check_in')
                    ->label('Arrivée')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('check_out')
                    ->label('Départ')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('guests')
                    ->label('Invités')
                    ->getStateUsing(fn ($record) => $record->guests_adults + $record->guests_children)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency->code)
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                        'gray' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'confirmed' => 'Confirmée',
                        'cancelled' => 'Annulée',
                        'completed' => 'Terminée',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'confirmed' => 'Confirmée',
                        'cancelled' => 'Annulée',
                        'completed' => 'Terminée',
                    ]),

                Tables\Filters\Filter::make('dates')
                    ->form([
                        Forms\Components\DatePicker::make('check_in_from')
                            ->label('Arrivée du'),
                        Forms\Components\DatePicker::make('check_in_to')
                            ->label('Arrivée au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['check_in_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in', '>=', $date),
                            )
                            ->when(
                                $data['check_in_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('accommodation')
                    ->label('Hébergement')
                    ->relationship('room.accommodation', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('confirm')
                    ->label('Confirmer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Booking $record) => $record->confirm())
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => $record->status === 'pending'),

                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Raison d\'annulation')
                            ->required(),
                    ])
                    ->action(fn (Booking $record, array $data) => $record->cancel($data['reason']))
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record) => in_array($record->status, ['pending', 'confirmed'])),

                Tables\Actions\Action::make('print_voucher')
                    ->label('Imprimer voucher')
                    ->icon('heroicon-o-printer')
                    ->action(fn (Booking $record) => response()->download($record->generateVoucher()))
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('confirm')
                        ->label('Confirmer')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->confirm())
                        ->requiresConfirmation()
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
