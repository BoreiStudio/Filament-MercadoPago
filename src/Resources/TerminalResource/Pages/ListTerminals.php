<?php

namespace BoreiStudio\FilamentMercadoPago\Resources\TerminalResource\Pages;

use BoreiStudio\FilamentMercadoPago\Resources\TerminalResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions\Action;
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoTerminalService;
use Filament\Notifications\Notification;

class ListTerminals extends ListRecords
{
    protected static string $resource = TerminalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncTerminals')
                ->label('Sincronizar Terminales')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $service = app(MercadoPagoTerminalService::class);
                    try {
                        $count = $service->syncTerminalsFromApi();

                        Notification::make()
                            ->title("Sincronización completa")
                            ->body("Se sincronizaron {$count} terminales")
                            ->success()
                            ->send();

                        $this->redirect($this->getUrl()); // refresca la página
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error al sincronizar terminales')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
