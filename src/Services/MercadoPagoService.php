<?php

namespace BoreiStudio\FilamentMercadoPago\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Exception;

class MercadoPagoService
{
    public function redirectToAuth(): string
    {
        $clientId = config('filament-mercado-pago.client_id');
        $redirectUri = urlencode(config('filament-mercado-pago.redirect_uri'));

        return "https://auth.mercadopago.com.ar/authorization?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}";
    }

    public function handleCallback(string $code): bool
    {
        $response = Http::asForm()->post('https://api.mercadopago.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('filament-mercado-pago.client_id'),
            'client_secret' => config('filament-mercado-pago.client_secret'),
            'code' => $code,
            'redirect_uri' => config('filament-mercado-pago.redirect_uri'),
        ]);

        if (! $response->ok()) {
            return false;
        }

        $data = $response->json();

        Auth::user()->update([
            'mp_access_token' => $data['access_token'],
            'mp_refresh_token' => $data['refresh_token'],
            'mp_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
        ]);

        return true;
    }

    public function getCredentials(int $userId = null): ?array
    {
        $userId = $userId ?? Auth::id();
        if (! $userId) return null;

        $account = MercadoPagoAccount::where('user_id', $userId)->first();
        if (! $account) return null;

        try {
            return [
                'access_token'  => Crypt::decryptString($account->access_token),
                'refresh_token' => $account->refresh_token,
                'public_key'    => isset($account->public_key) ? Crypt::decryptString($account->public_key) : null,
                'scope'         => $account->scope,
                'user_id_mp'    => $account->user_id_mp,
                'expires_in'    => $account->expires_in,
            ];
        } catch (Exception $e) {
            Log::error('Error desencriptando credenciales de Mercado Pago: ' . $e->getMessage());
            return null;
        }
    }

    public function getAccessToken(int $userId = null): ?string
    {
        return $this->getCredentials($userId)['access_token'] ?? null;
    }

    public function getPublicKey(int $userId = null): ?string
    {
        return $this->getCredentials($userId)['public_key'] ?? null;
    }

    public function getRefreshToken(int $userId = null): ?string
    {
        return $this->getCredentials($userId)['refresh_token'] ?? null;
    }
}
