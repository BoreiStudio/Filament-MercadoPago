<?php

use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\DisconnectAccountAction;
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\ExchangeCodeForTokenAction;
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\GenerateAuthorizationUrlAction;
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\RefreshAccessTokenAction;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $settings = app(MercadoPagoApplicationSettings::class);
    $settings->client_id = 'APP_CLIENT_ID';
    $settings->client_secret = 'APP_CLIENT_SECRET';
    $settings->redirect_uri = 'https://example.com/mercadopago/oauth/callback';
    $settings->webhook_secret = 'TEST_WEBHOOK_SECRET';
    $settings->country = 'MLA';
    $settings->sandbox_mode = true;
    $settings->save();
});

it('generates authorization url with required params', function () {
    $action = app(GenerateAuthorizationUrlAction::class);
    $result = $action->execute();

    expect($result)->toHaveKey('url')
        ->and($result['url'])->toStartWith('https://auth.mercadopago.com.ar/authorization?')
        ->and($result['url'])->toContain('client_id=APP_CLIENT_ID')
        ->and($result['url'])->toContain('response_type=code')
        ->and($result['url'])->toContain('redirect_uri='.urlencode(config('app.url').'/mercadopago/oauth/callback'))
        ->and($result['url'])->toContain('state=')
        ->and($result['url'])->toContain('code_challenge=')
        ->and($result['url'])->toContain('code_challenge_method=S256');
});

it('validates a correctly signed state', function () {
    $action = app(GenerateAuthorizationUrlAction::class);
    $result = $action->execute();

    $stateData = $action->validateState($result['state']);

    expect($stateData)->toBeArray()
        ->and($stateData)->toHaveKeys(['tenant_id', 'tenant_type', 'code_verifier', 'expires_at']);
});

it('rejects an invalid state', function () {
    $action = app(GenerateAuthorizationUrlAction::class);

    $stateData = $action->validateState('invalid-state-tampered');

    expect($stateData)->toBeNull();
});

it('exchanges code for token successfully', function () {
    Http::fake([
        'api.mercadopago.com/oauth/token' => Http::response([
            'access_token' => 'APP_USR-12345-ACCESS_TOKEN',
            'refresh_token' => 'TG-12345-REFRESH_TOKEN',
            'public_key' => 'APP_USR-12345-PUBLIC_KEY',
            'user_id' => 987654321,
            'scope' => 'read write',
            'expires_in' => 21600,
        ]),
    ]);

    $action = app(ExchangeCodeForTokenAction::class);
    $account = $action->execute('test-auth-code', 'test-code-verifier');

    expect($account)->toBeInstanceOf(MercadoPagoAccount::class)
        ->and($account->mp_user_id)->toBe(987654321)
        ->and($account->access_token)->toBe('APP_USR-12345-ACCESS_TOKEN')
        ->and($account->refresh_token)->toBe('TG-12345-REFRESH_TOKEN')
        ->and($account->public_key)->toBe('APP_USR-12345-PUBLIC_KEY')
        ->and($account->status)->toBe('connected')
        ->and($account->live_mode)->toBeFalse();

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.mercadopago.com/oauth/token'
            && $request['grant_type'] === 'authorization_code'
            && $request['client_id'] === 'APP_CLIENT_ID'
            && $request['code'] === 'test-auth-code';
    });
});

it('disconnects account and clears tokens', function () {
    $account = MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'APP_USR-ACCESS',
        'refresh_token' => 'TG-REFRESH',
        'public_key' => 'APP_PUB',
        'status' => 'connected',
    ]);

    $action = app(DisconnectAccountAction::class);
    $action->execute($account);

    $account->refresh();

    expect($account->status)->toBe('disconnected')
        ->and($account->access_token)->toBeNull()
        ->and($account->refresh_token)->toBeNull();
});

it('refreshes access token successfully', function () {
    $account = MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'OLD_ACCESS_TOKEN',
        'refresh_token' => 'VALID_REFRESH_TOKEN',
        'public_key' => 'APP_PUB',
        'expires_at' => now()->subDay(),
        'status' => 'connected',
    ]);

    Http::fake([
        'api.mercadopago.com/oauth/token' => Http::response([
            'access_token' => 'NEW_ACCESS_TOKEN',
            'refresh_token' => 'NEW_REFRESH_TOKEN',
            'expires_in' => 21600,
        ]),
    ]);

    $action = app(RefreshAccessTokenAction::class);
    $action->execute($account);

    $account->refresh();

    expect($account->access_token)->toBe('NEW_ACCESS_TOKEN')
        ->and($account->refresh_token)->toBe('NEW_REFRESH_TOKEN')
        ->and($account->status)->toBe('connected')
        ->and($account->expires_at->timestamp)->toBeGreaterThan(now()->timestamp);
});

it('marks account as error when refresh token is revoked', function () {
    $account = MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'OLD_ACCESS_TOKEN',
        'refresh_token' => 'REVOKED_REFRESH_TOKEN',
        'public_key' => 'APP_PUB',
        'expires_at' => now()->subDay(),
        'status' => 'connected',
    ]);

    Http::fake([
        'api.mercadopago.com/oauth/token' => Http::response(null, 401),
    ]);

    $action = app(RefreshAccessTokenAction::class);
    $action->execute($account);

    $account->refresh();

    expect($account->status)->toBe('error');
});
