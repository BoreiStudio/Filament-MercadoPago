<?php

use BoreiStudio\FilamentMercadoPago\Support\Credentials\MercadoPagoCredentialsDTO;
use BoreiStudio\FilamentMercadoPago\Support\Http\MercadoPagoClient;

beforeEach(function () {
    $token = env('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');
    if (! $token) {
        $this->markTestSkipped('MERCADOPAGO_SANDBOX_ACCESS_TOKEN not set in .env');
    }

    $this->credentials = new MercadoPagoCredentialsDTO(
        accessToken: $token,
        publicKey: '',
        mpUserId: '',
        liveMode: false,
    );

    $this->client = new MercadoPagoClient($this->credentials);
});

it('can create a checkout pro preference', function () {
    $preference = $this->client->createPreference([
        'items' => [
            ['title' => 'Test Product', 'quantity' => 1, 'unit_price' => 100.00],
        ],
        'external_reference' => 'test-'.uniqid(),
    ]);

    expect($preference->id)->not->toBeNull();
    expect($preference->init_point)->toContain('mercadopago');
})->group('sandbox');

it('can fetch a payment from api', function () {
    $paymentId = env('MERCADOPAGO_SANDBOX_PAYMENT_ID');
    if (! $paymentId) {
        $this->markTestSkipped('MERCADOPAGO_SANDBOX_PAYMENT_ID not set');
    }

    $payment = $this->client->getPayment((int) $paymentId);

    expect($payment->id)->toBe((int) $paymentId);
    expect($payment->status)->toBeString();
})->group('sandbox');
