<?php

namespace BoreiStudio\FilamentMercadoPago\Resources;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoTerminal;
use BoreiStudio\FilamentMercadoPago\Resources\TerminalResource\Pages;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Actions\Action;
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoTerminalService;
use Filament\Notifications\Notification;

class TerminalResource extends Resource
{
    protected static ?string $model = MercadoPagoTerminal::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationGroup = 'Mercado Pago';
    protected static ?string $navigationLabel = 'Terminales';
    protected static ?string $pluralLabel = 'Terminales';
    protected static ?string $modelLabel = 'Terminal';

    protected bool $confirmingChange = false;
    protected $pendingRecord = null;
    protected $pendingState = null;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('terminal_id')
                    ->label('Terminal ID')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('external_pos_id')
                    ->label('External POS')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('store_id')
                    ->label('Store ID')
                    ->searchable(),

                SelectColumn::make('operating_mode')
    ->label('Modo Operativo')
    ->options([
        'PDV' => 'PDV',
        'STANDALONE' => 'STANDALONE',
    ])
    ->afterStateUpdated(function ($record, $state) {
        // Actualizar en Mercado Pago
        app(MercadoPagoTerminalService::class)
            ->updateOperatingMode($record->terminal_id, $state);

        // Guardar en la base de datos (Filament ya lo hace, pero lo forzamos por seguridad)
        $record->operating_mode = $state;
        $record->save();
    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Puedes agregar filtros si es necesario.
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTerminals::route('/'),
        ];
    }
}
