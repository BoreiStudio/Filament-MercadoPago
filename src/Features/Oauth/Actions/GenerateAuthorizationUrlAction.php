<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions;

use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class GenerateAuthorizationUrlAction
{
    public function __construct(
        private readonly MercadoPagoApplicationSettings $settings,
    ) {}

    public function execute(): array
    {
        $codeVerifier = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);

        $state = $this->buildState($codeVerifier);

        $authDomain = $this->getAuthDomain();

        $url = "https://{$authDomain}/authorization?".http_build_query([
            'client_id' => $this->settings->client_id,
            'response_type' => 'code',
            'redirect_uri' => config('app.url').'/mercadopago/oauth/callback',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return [
            'url' => $url,
            'state' => $state,
        ];
    }

    public function validateState(string $state): ?array
    {
        try {
            $payload = Crypt::decryptString($state);

            $data = json_decode($payload, true);

            if (! $data || ! isset($data['expires_at']) || now()->timestamp > $data['expires_at']) {
                return null;
            }

            return $data;
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildState(string $codeVerifier): string
    {
        $payload = json_encode([
            'tenant_id' => null,
            'tenant_type' => null,
            'code_verifier' => $codeVerifier,
            'expires_at' => now()->addHour()->timestamp,
        ]);

        return Crypt::encryptString($payload);
    }

    private function getAuthDomain(): string
    {
        return match ($this->settings->country) {
            'MLA' => 'auth.mercadopago.com.ar',
            'MLB' => 'auth.mercadopago.com.br',
            'MLC' => 'auth.mercadopago.cl',
            'MCO' => 'auth.mercadopago.com.co',
            'MLM' => 'auth.mercadopago.com.mx',
            'MPE' => 'auth.mercadopago.com.pe',
            'MLU' => 'auth.mercadopago.com.uy',
            default => 'auth.mercadopago.com.ar',
        };
    }

    private function generateCodeVerifier(): string
    {
        return Str::random(64);
    }

    private function generateCodeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }
}
