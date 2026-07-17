<?php

use BoreiStudio\FilamentMercadoPago\Contracts\MercadoPagoCredentials;
use BoreiStudio\FilamentMercadoPago\Support\Http\MercadoPagoClient;
use MercadoPago\MercadoPagoConfig;

beforeEach(function () {
    $this->credentials = Mockery::mock(MercadoPagoCredentials::class);
    $this->credentials->shouldReceive('getAccessToken')->andReturn('APP_USR-TEST-TOKEN');
});

it('creates mercadopago client with credentials', function () {
    $client = new MercadoPagoClient($this->credentials);

    expect($client)->toBeInstanceOf(MercadoPagoClient::class)
        ->and(MercadoPagoConfig::getAccessToken())->toBe('APP_USR-TEST-TOKEN');
});
