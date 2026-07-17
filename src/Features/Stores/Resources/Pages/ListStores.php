<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Features\Stores\Actions\SyncStoresFromApiAction;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\StoreResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListStores extends ListRecords
{
    protected static string $resource = StoreResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('external_id')
                    ->label('ID externo')
                    ->searchable(),

                TextColumn::make('mp_store_id')
                    ->label('ID MP'),

                TextColumn::make('pos_terminals_count')
                    ->label('Cajas')
                    ->counts('posTerminals'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sincronizar desde MP')
                ->icon('heroicon-o-arrow-path')
                ->action(function (SyncStoresFromApiAction $action) {
                    $count = $action->execute();

                    Notification::make()
                        ->title("{$count} sucursales sincronizadas.")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
