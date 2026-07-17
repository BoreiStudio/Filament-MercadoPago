<?php

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;
use BoreiStudio\FilamentMercadoPago\Support\Credentials\MercadoPagoAccountNotConnectedException;
use BoreiStudio\FilamentMercadoPago\Support\Credentials\MercadoPagoCredentialsDTO;
use BoreiStudio\FilamentMercadoPago\Support\Credentials\SingleTenantCredentialResolver;

beforeEach(function () {
    $this->settings = app(MercadoPagoApplicationSettings::class);
    $this->settings->client_id = 'TEST_CLIENT_ID';
    $this->settings->client_secret = 'TEST_CLIENT_SECRET';
    $this->settings->redirect_uri = 'https://example.com/oauth/callback';
    $this->settings->webhook_secret = 'TEST_WEBHOOK_SECRET';
    $this->settings->sandbox_mode = true;
    $this->settings->save();
});

it('returns credentials when an account exists', function () {
    MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'APP_USR-12345-67890',
        'refresh_token' => 'TG-12345-67890',
        'public_key' => 'APP_PUB-12345',
        'scope' => 'read write',
        'expires_at' => now()->addDays(30),
        'live_mode' => false,
        'status' => 'connected',
    ]);

    $resolver = app(SingleTenantCredentialResolver::class);
    $credentials = $resolver->resolve();

    expect($credentials)->toBeInstanceOf(MercadoPagoCredentialsDTO::class)
        ->and($credentials->getAccessToken())->toBe('APP_USR-12345-67890')
        ->and($credentials->getPublicKey())->toBe('APP_PUB-12345')
        ->and($credentials->getMpUserId())->toBe(123456789)
        ->and($credentials->isLiveMode())->toBeFalse();
});

it('returns application credentials from settings', function () {
    $resolver = app(SingleTenantCredentialResolver::class);
    $appCredentials = $resolver->applicationCredentials();

    expect($appCredentials)->toBe([
        'client_id' => 'TEST_CLIENT_ID',
        'client_secret' => 'TEST_CLIENT_SECRET',
        'redirect_uri' => 'https://example.com/oauth/callback',
        'sandbox_mode' => true,
    ]);
});

it('throws exception when no account exists', function () {
    $resolver = app(SingleTenantCredentialResolver::class);

    $resolver->resolve();
})->throws(MercadoPagoAccountNotConnectedException::class, 'No Mercado Pago account connected');

it('throws exception when account is disconnected', function () {
    MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'APP_USR-12345-67890',
        'public_key' => 'APP_PUB-12345',
        'status' => 'disconnected',
    ]);

    $resolver = app(SingleTenantCredentialResolver::class);

    expect(fn () => $resolver->resolve())->toThrow(MercadoPagoAccountNotConnectedException::class);
});
