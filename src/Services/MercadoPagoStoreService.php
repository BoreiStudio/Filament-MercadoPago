<?php

namespace BoreiStudio\FilamentMercadoPago\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use BoreiStudio\FilamentMercadoPago\Helpers\MercadoPagoHelper;

class MercadoPagoStoreService
{
    public static function normalizeBusinessHours(?array $raw): object
    {
        if (empty($raw)) return (object) [];

        $grouped = [];
        foreach ($raw as $item) {
            if (!isset($item['day'], $item['open'], $item['close'])) continue;

            $day = strtolower($item['day']);

            $grouped[$day][] = [
                'open' => substr($item['open'], 0, 5),
                'close' => substr($item['close'], 0, 5),
            ];
        }

        return (object) $grouped;
    }

    public static function syncStore(array $data, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        $accessToken = MercadoPagoHelper::getAccessTokenForUser($userId);
        $mpUserId = MercadoPagoHelper::getMpUserIdForUser($userId);

        if (!$accessToken || !$mpUserId) {
            throw new \Exception('Access Token o User ID de Mercado Pago no encontrado.');
        }

        $payload = [
            'name' => $data['name'],
            'external_id' => $data['external_id'],
            'business_hours' => self::normalizeBusinessHours($data['business_hours'] ?? []),
            'location' => [
                'street_number' => $data['street_number'],
                'street_name' => $data['street_name'],
                'city_name' => $data['city_name'],
                'state_name' => $data['state_name'],
                'latitude' => (float) $data['latitude'],
                'longitude' => (float) $data['longitude'],
                'reference' => $data['reference'] ?? null,
            ],
        ];

        $endpointBase = "https://api.mercadopago.com/users/{$mpUserId}/stores";

        // Buscar si existe
        $search = Http::withToken($accessToken)
            ->get("{$endpointBase}/search", ['external_id' => $data['external_id']]);

        Log::debug('MP - Buscando sucursal', [
            'url' => "{$endpointBase}/search",
            'external_id' => $data['external_id'],
            'response' => $search->json(),
        ]);

        if ($search->successful() && count($search->json('results')) > 0) {
            $storeId = $search->json('results.0.id');
            $response = Http::withToken($accessToken)
                ->put("{$endpointBase}/{$storeId}", $payload);
        } else {
            $response = Http::withToken($accessToken)
                ->post($endpointBase, $payload);
        }

        if ($response->failed()) {
            throw new \Exception('Error al sincronizar tienda: ' . $response->body());
        }

        return $response->json();
    }
}
