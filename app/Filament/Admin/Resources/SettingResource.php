<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SettingResource\Pages;
use App\Models\Setting;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Paramètre';

    protected static ?string $pluralModelLabel = 'Paramètres';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Paramètre système')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Clé')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled(fn ($record) => $record !== null)
                            ->helperText('Identifiant unique du paramètre (ne peut pas être modifié)'),

                        Forms\Components\Select::make('type')
                            ->label('Type de valeur')
                            ->options([
                                'string' => 'Texte',
                                'integer' => 'Nombre entier',
                                'decimal' => 'Nombre décimal',
                                'boolean' => 'Booléen (Oui/Non)',
                                'json' => 'JSON (Tableau/Objet)',
                            ])
                            ->required()
                            ->reactive()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\Group::make()
                            ->schema(fn (Forms\Get $get) => match($get('type')) {
                                'string' => [
                                    Forms\Components\TextInput::make('value')
                                        ->label('Valeur')
                                        ->required()
                                        ->maxLength(65535),
                                ],
                                'integer' => [
                                    Forms\Components\TextInput::make('value')
                                        ->label('Valeur')
                                        ->numeric()
                                        ->integer()
                                        ->required(),
                                ],
                                'decimal' => [
                                    Forms\Components\TextInput::make('value')
                                        ->label('Valeur')
                                        ->numeric()
                                        ->required(),
                                ],
                                'boolean' => [
                                    Forms\Components\Toggle::make('value')
                                        ->label('Valeur')
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->onIcon('heroicon-o-check')
                                        ->offIcon('heroicon-o-x-mark'),
                                ],
                                'json' => [
                                    Forms\Components\Textarea::make('value')
                                        ->label('Valeur (JSON)')
                                        ->required()
                                        ->rows(5)
                                        ->helperText('Doit être un JSON valide'),
                                ],
                                default => [],
                            }),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Description du paramètre pour les administrateurs'),
                    ]),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Clé')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Clé copiée'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->formatStateUsing(function ($state, $record) {
                        return match($record->type) {
                            'boolean' => $state === '1' ? 'Oui' : 'Non',
                            'json' => json_encode(json_decode($state, false, 512, JSON_THROW_ON_ERROR), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                            default => Str::limit($state, 50),
                        };
                    })
                    ->badge()
                    ->color(fn ($record) => match($record->type) {
                        'boolean' => $record->value === '1' ? 'success' : 'danger',
                        'json' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Type')
                    ->colors([
                        'primary' => 'string',
                        'success' => 'integer',
                        'warning' => 'decimal',
                        'danger' => 'boolean',
                        'gray' => 'json',
                    ]),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'string' => 'Texte',
                        'integer' => 'Nombre entier',
                        'decimal' => 'Nombre décimal',
                        'boolean' => 'Booléen',
                        'json' => 'JSON',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('clear_cache')
                    ->label('Vider le cache')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        Cache::forget('settings.all');
                        Cache::tags('settings')->flush();

                        Notification::make()
                            ->success()
                            ->title('Cache vidé')
                            ->body('Le cache des paramètres a été vidé.')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('key')
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label('Type')
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'view' => Pages\ViewSetting::route('/{record}'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        // Limiter la création de nouveaux paramètres aux super admins
        return auth()->user()?->email === 'admin@'.config('app.domain');
    }
}
