<?php

namespace BoreiStudio\FilamentMercadoPago\Resources;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoStore;
use BoreiStudio\FilamentMercadoPago\Resources\StoreResource\Pages;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions;
use BoreiStudio\FilamentMercadoPago\Helpers\MercadoPagoHelper;
use Illuminate\Support\Facades\Http;
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoService;

class StoreResource extends Resource
{
    protected static ?string $model = MercadoPagoStore::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Mercado Pago';
    protected static ?string $navigationLabel = 'Sucursales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('external_id')
                    ->label('ID en Mercado Pago')
                    ->dehydrated()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la Sucursal')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Fieldset::make('Ubicación')
                    ->schema([
                        Forms\Components\TextInput::make('street_name')
                            ->label('Calle')
                            ->required(),

                        Forms\Components\TextInput::make('street_number')
                            ->label('Número')
                            ->required(),

                        Forms\Components\TextInput::make('city_name')
                            ->label('Ciudad')
                            ->required(),

                        Forms\Components\TextInput::make('state_name')
                            ->label('Provincia/Estado')
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitud')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitud')
                            ->numeric()
                            ->required(),
                    ]),

                Forms\Components\Fieldset::make('Horario Comercial')
                    ->schema([
                        Repeater::make('business_hours')
                            ->schema([
                                Forms\Components\TextInput::make('day')
                                    ->label('Día')
                                    ->columnSpan(1),
                                Forms\Components\TimePicker::make('open')
                                    ->label('Apertura')
                                    ->columnSpan(1),
                                Forms\Components\TimePicker::make('close')
                                    ->label('Cierre')
                                    ->columnSpan(1),
                            ])
                            ->label('')
                            ->defaultItems(2)
                            ->columns(3)
                            ->columnSpanFull(),
                        ]),
                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->default(true),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('external_id')->label('ID MP')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('location')->label('Dirección')->limit(50),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Creado'),
            ])
            ->filters([
                // Agrega filtros si fuera necesario
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Actions\Action::make('syncWithMercadoPago')
                    ->label('Sincronizar con Mercado Pago')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $data = [
                            'name' => $record->name,
                            'external_id' => $record->external_id,
                            'business_hours' => $record->getBusinessHoursForApi(),
                            'street_number' => $record->street_number,
                            'street_name' => $record->street_name,
                            'city_name' => $record->city_name,
                            'state_name' => $record->state_name,
                            'latitude' => $record->latitude,
                            'longitude' => $record->longitude,
                            'reference' => $record->reference,
                        ];

                        $response = MercadoPagoService::syncStore($data, $record->user_id ?? auth()->id());

                        $record->external_id = $response['id'] ?? $record->external_id;
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Tienda sincronizada')
                            ->success()
                            ->body("Tienda sincronizada con ID: {$record->external_id}")
                            ->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}
