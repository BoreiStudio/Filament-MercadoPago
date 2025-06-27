<?php

namespace BoreiStudio\FilamentMercadoPago\Resources\PlanResource\Pages;

use BoreiStudio\FilamentMercadoPago\Resources\PlanResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use BoreiStudio\FilamentMercadoPago\Helpers\MercadoPagoHelper;
use Illuminate\Support\Facades\Http;
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoPlanService;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('syncWithMercadoPago')
                ->label('Sincronizar desde MP')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    app(MercadoPagoPlanService::class)->syncPlansFromApi();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Sincronización completada')
                        ->success()
                        ->send();
                }),
        ];
    }
}
