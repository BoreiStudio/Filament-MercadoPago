<?php

namespace BoreiStudio\FilamentMercadoPago\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class MercadoPagoHelper
{
    public static function getAccessTokenForUser(?int $userId = null): ?string
    {
        $userId = $userId ?? Auth::id();

        $encryptedToken = DB::table('mercado_pago_accounts')
            ->where('user_id', $userId)
            ->value('access_token');

        if (! $encryptedToken) {
            return null;
        }

        try {
            // Usar decryptString porque fue encryptString
            return Crypt::decryptString($encryptedToken);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getMpUserIdForUser(?int $userId = null): ?string
    {
        $userId = $userId ?? Auth::id();

        return DB::table('mercado_pago_accounts')
            ->where('user_id', $userId)
            ->value('user_id_mp');  // asumo que la columna donde guardás el user_id de MP es mp_user_id
    }

    public static function listAndSaveStores(?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        $accessToken = self::getAccessTokenForUser($userId);
        $mpUserId = self::getMpUserIdForUser($userId);

        if (! $accessToken || ! $mpUserId) {
            return false;
        }

        $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
            ->get("https://api.mercadopago.com/users/{$mpUserId}/stores/search");

        if (! $response->successful()) {
            return false;
        }

        $stores = $response->json('results');

        if (empty($stores)) {
            return true;
        }

        foreach ($stores as $store) {
            \BoreiStudio\FilamentMercadoPago\Models\MercadoPagoStore::updateOrCreate(
                ['external_id' => $store['external_id'] ?? $store['id']],
                [
                    'user_id' => $userId,
                    'name' => $store['name'] ?? null,
                    'location' => isset($store['address']) ? json_encode($store['address']) : null,
                    'active' => ($store['status'] ?? '') === 'active',
                ]
            );
        }

        return true;
    }

}
