<?php

namespace BoreiStudio\FilamentMercadoPago\Resources\PlanResource\Pages;

use BoreiStudio\FilamentMercadoPago\Resources\PlanResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use BoreiStudio\FilamentMercadoPago\Helpers\MercadoPagoHelper;
use Illuminate\Support\Facades\Http;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('syncFromMercadoPago')
                ->label('Sincronizar desde MP')
                ->icon('heroicon-o-arrow-path')
                ->color('success') // Botón verde de éxito
                ->requiresConfirmation()
                ->modalHeading('Confirmar sincronización')
                ->modalSubheading('Esta acción sincronizará solo los planes activos desde Mercado Pago.')
                ->modalButton('Sincronizar')
                ->action(function () {
                    $userId = auth()->id();
                    $accessToken = MercadoPagoHelper::getAccessTokenForUser($userId);

                    if (!$accessToken) {
                        throw new \Exception('No se encontró un access_token para este usuario.');
                    }

                    $endpoint = 'https://api.mercadopago.com/preapproval_plan/search';

                    $response = Http::withToken($accessToken)->get($endpoint);

                    if ($response->failed()) {
                        throw new \Exception('Error al obtener planes de Mercado Pago: ' . $response->body());
                    }

                    $data = $response->json();

                    $plans = $data['results'] ?? [];

                    $activePlansCount = 0;

                    foreach ($plans as $plan) {
                        if (($plan['status'] ?? '') !== 'active') {
                            // Ignorar planes que no estén activos
                            continue;
                        }

                        \BoreiStudio\FilamentMercadoPago\Models\Plan::updateOrCreate(
                            ['external_id' => $plan['id']],
                            [
                                'user_id' => $userId,
                                'name' => $plan['reason'] ?? 'Sin nombre',
                                'amount' => $plan['auto_recurring']['transaction_amount'] ?? 0,
                                'currency' => $plan['auto_recurring']['currency_id'] ?? 'ARS',
                                'frequency' => $plan['auto_recurring']['frequency'] ?? 1,
                                'frequency_type' => $plan['auto_recurring']['frequency_type'] ?? 'months',
                                'repetitions' => $plan['auto_recurring']['repetitions'] ?? 0,
                                'status' => true,
                                'description' => null,
                            ]
                        );

                        $activePlansCount++;
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Planes sincronizados desde Mercado Pago')
                        ->success()
                        ->body("{$activePlansCount} planes activos sincronizados.")
                        ->send();
                }),
        ];
    }
}
