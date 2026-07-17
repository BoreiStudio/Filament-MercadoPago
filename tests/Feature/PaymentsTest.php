<?php

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\CreatePreferenceAction;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\SyncPaymentFromApiAction;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Support\Http\MercadoPagoClient;
use MercadoPago\Resources\Payment as MPayment;
use MercadoPago\Resources\Preference;

beforeEach(function () {
    $this->account = MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'APP_USR-TEST-TOKEN',
        'refresh_token' => 'TG-REFRESH',
        'public_key' => 'APP_PUB',
        'status' => 'connected',
    ]);
});

it('creates preference successfully', function () {
    $preference = new Preference;
    $preference->id = '123456789-preference';
    $preference->init_point = 'https://www.mercadopago.com/checkout/redirect?pref_id=123456789';
    $preference->sandbox_init_point = 'https://sandbox.mercadopago.com/checkout/redirect?pref_id=123456789';

    $client = Mockery::mock(MercadoPagoClient::class);
    $client->shouldReceive('createPreference')
        ->once()
        ->andReturn($preference);

    $resolver = app(CredentialResolverInterface::class);
    $action = new CreatePreferenceAction($resolver, $client);

    $result = $action->execute(
        items: [
            ['title' => 'Producto 1', 'quantity' => 1, 'unit_price' => 100.00],
        ],
        externalReference: 'order-123',
        backUrls: ['success' => 'https://ejemplo.com/success'],
    );

    expect($result)->toHaveKey('init_point')
        ->and($result)->toHaveKey('payment')
        ->and($result['payment'])->toBeInstanceOf(Payment::class)
        ->and($result['payment']->status)->toBe('pending')
        ->and($result['payment']->external_reference)->toBe('order-123')
        ->and($result['payment']->source)->toBe('checkout_pro');
});

it('syncs payment from api', function () {
    $mpPayment = new MPayment;
    $mpPayment->id = 98765;
    $mpPayment->status = 'approved';
    $mpPayment->status_detail = 'accredited';
    $mpPayment->transaction_amount = 250.00;
    $mpPayment->currency_id = 'ARS';
    $mpPayment->payment_type_id = 'credit_card';
    $mpPayment->payment_method_id = 'visa';
    $mpPayment->external_reference = 'order-456';
    $mpPayment->date_approved = '2026-07-16T14:00:00.000-03:00';
    $mpPayment->date_last_updated = null;
    $mpPayment->payer = new stdClass;
    $mpPayment->payer->email = 'comprador@ejemplo.com';

    $client = Mockery::mock(MercadoPagoClient::class);
    $client->shouldReceive('getPayment')
        ->with(98765)
        ->once()
        ->andReturn($mpPayment);

    $resolver = app(CredentialResolverInterface::class);
    $action = new SyncPaymentFromApiAction($resolver, $client);

    $payment = $action->execute(98765);

    expect($payment)->toBeInstanceOf(Payment::class)
        ->and($payment->mp_payment_id)->toBe(98765)
        ->and($payment->status)->toBe('approved')
        ->and((float) $payment->transaction_amount)->toBe(250.00)
        ->and($payment->payment_method_id)->toBe('visa')
        ->and($payment->payer_email)->toBe('comprador@ejemplo.com');
});

it('updates existing payment on sync', function () {
    Payment::create([
        'account_id' => $this->account->id,
        'mp_payment_id' => 98765,
        'status' => 'pending',
        'transaction_amount' => 250.00,
        'external_reference' => 'order-456',
    ]);

    $mpPayment = new MPayment;
    $mpPayment->id = 98765;
    $mpPayment->status = 'approved';
    $mpPayment->status_detail = 'accredited';
    $mpPayment->transaction_amount = 250.00;
    $mpPayment->currency_id = 'ARS';
    $mpPayment->payment_type_id = 'credit_card';
    $mpPayment->payment_method_id = 'visa';
    $mpPayment->external_reference = 'order-456';
    $mpPayment->date_approved = '2026-07-16T14:00:00.000-03:00';
    $mpPayment->date_last_updated = null;
    $mpPayment->payer = new stdClass;
    $mpPayment->payer->email = 'comprador@ejemplo.com';

    $client = Mockery::mock(MercadoPagoClient::class);
    $client->shouldReceive('getPayment')
        ->with(98765)
        ->once()
        ->andReturn($mpPayment);

    $resolver = app(CredentialResolverInterface::class);
    $action = new SyncPaymentFromApiAction($resolver, $client);

    $payment = $action->execute(98765);

    expect(Payment::count())->toBe(1)
        ->and($payment->status)->toBe('approved');
});
