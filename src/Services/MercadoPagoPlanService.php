<?php

namespace BoreiStudio\FilamentMercadoPago\Services;

use BoreiStudio\FilamentMercadoPago\Helpers\MercadoPagoHelper;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoPlan;
use Illuminate\Support\Facades\Http;
use Exception;

class MercadoPagoPlanService
{
    /**
     * Crear plan de suscripción en Mercado Pago y guardarlo en la base de datos.
     */
    public function createPlan(array $data, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        $accessToken = MercadoPagoHelper::getAccessTokenForUser($userId);

        if (!$accessToken) {
            throw new \Exception('Access Token no encontrado.');
        }

        $payload = [
            'reason' => $data['name'],
            'back_url' => config('app.url') . '/checkout/success',
            'auto_recurring' => [
                'frequency' => (int) $data['frequency'],
                'frequency_type' => $data['frequency_type'],
                'transaction_amount' => (float) $data['amount'],
                'currency_id' => $data['currency'] ?? 'ARS',
            ],
        ];

        if (!empty($data['repetitions'])) {
            $payload['auto_recurring']['repetitions'] = (int) $data['repetitions'];
        }

        $response = Http::withToken($accessToken)
            ->post('https://api.mercadopago.com/preapproval_plan', $payload);

        if ($response->failed()) {
            throw new \Exception('Error al crear plan en Mercado Pago: ' . $response->body());
        }

        $result = $response->json();

        Plan::updateOrCreate(
            ['external_id' => $result['id']],
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? '',
                'amount' => (float) $data['amount'],
                'currency' => $data['currency'] ?? 'ARS',
                'frequency' => (int) $data['frequency'],
                'frequency_type' => $data['frequency_type'],
                'repetitions' => (int) ($data['repetitions'] ?? 0),
                'status' => $result['status'] ?? 'active',
                'metadata' => $result['metadata'] ?? [],
            ]
        );

        return $result;
    }


    public function syncPlansFromApi(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        $accessToken = MercadoPagoHelper::getAccessTokenForUser($userId);

        if (!$accessToken) {
            throw new \Exception('Access Token no encontrado.');
        }

        $endpoint = 'https://api.mercadopago.com/preapproval_plan/search';
        $limit = 50;
        $offset = 0;

        do {
            $response = Http::withToken($accessToken)->get($endpoint, [
                'status' => 'active', // Puedes ajustar o eliminar este filtro según necesidades
                'limit' => $limit,
                'offset' => $offset,
            ]);

            if ($response->failed()) {
                throw new \Exception('Error al sincronizar planes: ' . $response->body());
            }

            $plans = $response->json('results', []);

            foreach ($plans as $plan) {
                MercadoPagoPlan::updateOrCreate(
                    ['external_id' => $plan['id']],
                    [
                        'name' => $plan['reason'] ?? 'Sin nombre',
                        'description' => $plan['reason'] ?? '',
                        'amount' => data_get($plan, 'auto_recurring.transaction_amount', 0),
                        'currency' => data_get($plan, 'auto_recurring.currency_id', 'ARS'),
                        'frequency' => data_get($plan, 'auto_recurring.frequency', 1),
                        'frequency_type' => data_get($plan, 'auto_recurring.frequency_type', 'months'),
                        'repetitions' => data_get($plan, 'auto_recurring.repetitions', 0),
                        'status' => $plan['status'] === 'active', // Si status es string, convertí a bool si tu modelo lo espera así
                        'metadata' => $plan['metadata'] ?? [],
                    ]
                );
            }

            // Avanzamos el offset para la siguiente página
            $offset += $limit;

            // El API no siempre devuelve el total count, podrías parar si la respuesta es menor que el límite
            $fetchedCount = count($plans);

        } while ($fetchedCount === $limit);

        return true;
    }


    public function syncPlan(MercadoPagoPlan $plan, ?int $userId = null): MercadoPagoPlan
    {
        $userId = $userId ?? auth()->id();

        $accessToken = MercadoPagoHelper::getAccessTokenForUser($userId);

        if (!$accessToken) {
            throw new \Exception('No se encontró un access_token para este usuario.');
        }

        $autoRecurring = [
            'frequency' => $plan->frequency,
            'frequency_type' => $plan->frequency_type,
            'transaction_amount' => (float) $plan->amount,
            'currency_id' => $plan->currency,
        ];

        if (($plan->repetitions ?? 0) >= 1) {
            $autoRecurring['repetitions'] = (int) $plan->repetitions;
        }

        $payload = [
            'reason' => $plan->name,
            'back_url' => config('app.url') . '/checkout/success',
            'auto_recurring' => $autoRecurring,
        ];

        $endpointBase = 'https://api.mercadopago.com/preapproval_plan';

        if ($plan->external_id) {
            // Actualizar plan existente
            $endpoint = $endpointBase . '/' . $plan->external_id;
            $response = Http::withToken($accessToken)->put($endpoint, $payload);
        } else {
            // Crear plan nuevo
            $endpoint = $endpointBase;
            $response = Http::withToken($accessToken)->post($endpoint, $payload);
        }

        if ($response->failed()) {
            throw new \Exception('Error al sincronizar el plan: ' . $response->body());
        }

        $plan->external_id = $response['id'] ?? $plan->external_id;
        $plan->save();

        return $plan;
    }
}
