<?php

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;
use BoreiStudio\FilamentMercadoPago\Support\Credentials\MercadoPagoAccountNotConnectedException;
use BoreiStudio\FilamentMercadoPago\Support\Credentials\MercadoPagoCredentialsDTO;
use BoreiStudio\FilamentMercadoPago\Support\Credentials\MultiTenantCredentialResolver;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestTenant extends Model
{
    public $table = 'test_tenants';

    public $timestamps = false;

    protected $fillable = ['id'];
}

beforeEach(function () {
    Schema::create('test_tenants', function (Blueprint $table) {
        $table->id();
    });

    $this->settings = app(MercadoPagoApplicationSettings::class);
    $this->settings->client_id = 'TEST_CLIENT_ID';
    $this->settings->client_secret = 'TEST_CLIENT_SECRET';
    $this->settings->redirect_uri = 'https://example.com/oauth/callback';
    $this->settings->webhook_secret = 'TEST_WEBHOOK_SECRET';
    $this->settings->sandbox_mode = false;
    $this->settings->save();
});

it('returns credentials for current tenant', function () {
    $tenant = TestTenant::create(['id' => 1]);

    MercadoPagoAccount::create([
        'tenant_id' => $tenant->id,
        'tenant_type' => $tenant->getMorphClass(),
        'mp_user_id' => 987654321,
        'access_token' => 'APP_USR-TENANT-ACCESS',
        'refresh_token' => 'TG-TENANT-REFRESH',
        'public_key' => 'APP_PUB-TENANT',
        'scope' => 'read write offline_access',
        'expires_at' => now()->addDays(30),
        'live_mode' => true,
        'status' => 'connected',
    ]);

    Filament::shouldReceive('getTenant')->andReturn($tenant);

    $resolver = app(MultiTenantCredentialResolver::class);
    $credentials = $resolver->resolve();

    expect($credentials)->toBeInstanceOf(MercadoPagoCredentialsDTO::class)
        ->and($credentials->getAccessToken())->toBe('APP_USR-TENANT-ACCESS')
        ->and($credentials->getPublicKey())->toBe('APP_PUB-TENANT')
        ->and($credentials->getMpUserId())->toBe(987654321)
        ->and($credentials->isLiveMode())->toBeTrue();
});

it('throws exception when no tenant context', function () {
    Filament::shouldReceive('getTenant')->andReturn(null);

    $resolver = app(MultiTenantCredentialResolver::class);
    $resolver->resolve();
})->throws(MercadoPagoAccountNotConnectedException::class, 'No tenant context available');

it('throws exception when tenant has no account', function () {
    $tenant = TestTenant::create(['id' => 2]);

    Filament::shouldReceive('getTenant')->andReturn($tenant);

    $resolver = app(MultiTenantCredentialResolver::class);

    expect(fn () => $resolver->resolve())->toThrow(MercadoPagoAccountNotConnectedException::class);
});
