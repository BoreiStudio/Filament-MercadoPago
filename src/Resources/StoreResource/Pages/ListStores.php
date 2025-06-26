<?php

namespace BoreiStudio\FilamentMercadoPago\Resources\StoreResource\Pages;

use BoreiStudio\FilamentMercadoPago\Resources\StoreResource;
use BoreiStudio\FilamentMercadoPago\Helpers\MercadoPagoHelper;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListStores extends ListRecords
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('syncStores')
                ->label('Sincronizar Sucursales')
                ->action(function () {
                    $result = MercadoPagoHelper::listAndSaveStores();

                    if ($result) {
                        Notification::make()
                            ->title('Sucursales sincronizadas correctamente')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error al sincronizar sucursales')
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->color('success')
                ->icon('heroicon-o-arrow-path'),
        ];
    }
}
