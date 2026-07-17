<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Features\Pos\Actions\SyncPosFromApiAction;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\PosResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListPos extends ListRecords
{
    protected static string $resource = PosResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('store.name')
                    ->label('Sucursal')
                    ->searchable(),

                TextColumn::make('external_id')
                    ->label('ID externo')
                    ->searchable(),

                TextColumn::make('mp_pos_id')
                    ->label('ID MP'),

                IconColumn::make('fixed_amount')
                    ->label('Monto fijo')
                    ->boolean(),

                TextColumn::make('category')
                    ->label('Categoría'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('ver_qr')
                    ->label('Ver QR')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn ($record) => $record->qr_image_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->qr_image_url)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sincronizar desde MP')
                ->icon('heroicon-o-arrow-path')
                ->action(function (SyncPosFromApiAction $action) {
                    $count = $action->execute();

                    Notification::make()
                        ->title("{$count} cajas sincronizadas.")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
