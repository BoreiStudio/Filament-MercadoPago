<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;
use Illuminate\Support\Facades\Http;

class RefreshAccessTokenAction
{
    public function __construct(
        private readonly MercadoPagoApplicationSettings $settings,
    ) {}

    public function execute(MercadoPagoAccount $account): MercadoPagoAccount
    {
        $response = Http::asForm()->post('https://api.mercadopago.com/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->settings->client_id,
            'client_secret' => $this->settings->client_secret,
            'refresh_token' => $account->refresh_token,
        ]);

        if ($response->unauthorized()) {
            $account->update([
                'status' => 'error',
                'last_refreshed_at' => now(),
            ]);

            return $account;
        }

        $response->throw();

        $data = $response->json();

        $account->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
            'expires_at' => now()->addSeconds($data['expires_in'] ?? 21600),
            'status' => 'connected',
            'last_refreshed_at' => now(),
        ]);

        return $account;
    }
}
