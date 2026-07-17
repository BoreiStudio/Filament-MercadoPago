<?php

namespace BoreiStudio\FilamentMercadoPago\Support\Credentials;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;

class SingleTenantCredentialResolver implements CredentialResolverInterface
{
    public function __construct(
        private readonly MercadoPagoApplicationSettings $settings,
    ) {}

    public function resolve(): MercadoPagoCredentialsDTO
    {
        $account = MercadoPagoAccount::query()
            ->whereNull('tenant_id')
            ->whereNull('tenant_type')
            ->first();

        if (! $account || ! $account->isConnected()) {
            throw new MercadoPagoAccountNotConnectedException(
                'No Mercado Pago account connected. Please connect your account via OAuth.'
            );
        }

        return new MercadoPagoCredentialsDTO(
            accessToken: $account->access_token,
            publicKey: $account->public_key ?? '',
            mpUserId: (int) $account->mp_user_id,
            liveMode: $account->live_mode,
        );
    }

    public function applicationCredentials(): array
    {
        return [
            'client_id' => $this->settings->client_id,
            'client_secret' => $this->settings->client_secret,
            'redirect_uri' => $this->settings->redirect_uri,
            'sandbox_mode' => $this->settings->sandbox_mode,
        ];
    }
}
