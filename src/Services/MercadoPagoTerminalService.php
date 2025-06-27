<?php

namespace BoreiStudio\FilamentMercadoPago\Services;

use BoreiStudio\FilamentMercadoPago\Helpers\MercadoPagoHelper;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoTerminal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoTerminalService
{
    /**
     * Obtener lista de terminales desde Mercado Pago
     */
    public function listTerminals(array $queryParams = [], ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();

        $accessToken = MercadoPagoHelper::getAccessTokenForUser($userId);

        if (! $accessToken) {
            throw new \Exception('Access Token no disponible');
        }

        $endpoint = 'https://api.mercadopago.com/terminals/v1/list';

        $response = Http::withToken($accessToken)
            ->get($endpoint, $queryParams);

        if ($response->failed()) {
            throw new \Exception('Error al obtener terminales: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Sincroniza terminales desde Mercado Pago y guarda en DB
     *
     * @param array $queryParams
     * @param int|null $userId
     * @return int Cantidad de terminales sincronizadas
     * @throws \Exception
     */
    public function syncTerminalsFromApi(array $queryParams = [], ?int $userId = null): int
    {
        $userId = $userId ?? auth()->id();

        $data = $this->listTerminals($queryParams, $userId);

        if (!isset($data['data']['terminals']) || !is_array($data['data']['terminals'])) {
            Log::warning('No se encontraron terminales en la respuesta de MP', ['response' => $data]);
            return 0;
        }

        $terminals = $data['data']['terminals'];

        $count = 0;
        foreach ($terminals as $terminal) {
            MercadoPagoTerminal::updateOrCreate(
                ['terminal_id' => $terminal['id']],
                [
                    'pos_id' => $terminal['pos_id'] ?? null,
                    'store_id' => $terminal['store_id'] ?? null,
                    'external_pos_id' => $terminal['external_pos_id'] ?? null,
                    'operating_mode' => $terminal['operating_mode'] ?? null,
                ]
            );
            $count++;
        }

        return $count;
    }

    public function updateOperatingMode(string $terminalId, string $operatingMode, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        $accessToken = MercadoPagoHelper::getAccessTokenForUser($userId);

        if (! $accessToken) {
            throw new \Exception('Access Token no disponible');
        }

        $endpoint = 'https://api.mercadopago.com/terminals/v1/setup';

        $payload = [
            'terminals' => [
                [
                    'id' => $terminalId,
                    'operating_mode' => $operatingMode,
                ],
            ],
        ];

        Log::debug('MP - Actualizando modo operativo terminal', compact('terminalId', 'operatingMode', 'endpoint', 'payload'));

        $response = Http::withToken($accessToken)
            ->patch($endpoint, $payload);

        Log::debug('MP - Respuesta actualización modo operativo', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            throw new \Exception('Error al actualizar modo operativo: ' . $response->body());
        }

        return $response->json();
    }
}
