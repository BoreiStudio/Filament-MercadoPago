<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;
use Illuminate\Support\Facades\Http;

class ExchangeCodeForTokenAction
{
    public function __construct(
        private readonly MercadoPagoApplicationSettings $settings,
    ) {}

    public function execute(string $code, string $codeVerifier, ?string $tenantId = null, ?string $tenantType = null): MercadoPagoAccount
    {
        $response = Http::post('https://api.mercadopago.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $this->settings->client_id,
            'client_secret' => $this->settings->client_secret,
            'code' => $code,
            'redirect_uri' => config('app.url').'/mercadopago/oauth/callback',
            'code_verifier' => $codeVerifier,
        ]);

        $response->throw();

        $data = $response->json();

        $account = MercadoPagoAccount::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'tenant_type' => $tenantType,
            ],
            [
                'mp_user_id' => $data['user_id'],
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'public_key' => $data['public_key'] ?? '',
                'scope' => $data['scope'] ?? '',
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 21600),
                'live_mode' => ! ($this->settings->sandbox_mode),
                'status' => 'connected',
                'last_refreshed_at' => now(),
            ]
        );

        return $account;
    }
}
