<?php

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Actions\CreateRefundAction;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Models\Refund;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Support\Http\MercadoPagoClient;

beforeEach(function () {
    $this->account = MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'APP_USR-TEST',
        'public_key' => 'APP_PUB',
        'status' => 'connected',
    ]);

    $this->payment = Payment::create([
        'account_id' => $this->account->id,
        'mp_payment_id' => 55555,
        'status' => 'approved',
        'transaction_amount' => 500.00,
        'external_reference' => 'order-refund-test',
    ]);
});

it('processes full refund', function () {
    $client = Mockery::mock(MercadoPagoClient::class);
    $client->shouldReceive('createRefund')
        ->with(55555, null)
        ->once()
        ->andReturn(['id' => 999, 'status' => 'approved']);

    $resolver = app(CredentialResolverInterface::class);
    $action = new CreateRefundAction($resolver, $client);

    $refund = $action->execute($this->payment);

    expect($refund)->toBeInstanceOf(Refund::class)
        ->and((float) $refund->amount)->toBe(500.00)
        ->and($refund->status)->toBe('approved');

    $this->payment->refresh();

    expect($this->payment->status)->toBe('refunded');
});

it('processes partial refund', function () {
    $client = Mockery::mock(MercadoPagoClient::class);
    $client->shouldReceive('createRefund')
        ->with(55555, 200.00)
        ->once()
        ->andReturn(['id' => 1000, 'status' => 'approved']);

    $resolver = app(CredentialResolverInterface::class);
    $action = new CreateRefundAction($resolver, $client);

    $refund = $action->execute($this->payment, 200.00);

    expect((float) $refund->amount)->toBe(200.00);

    $this->payment->refresh();

    expect($this->payment->status)->toBe('partially_refunded');
});

it('rejects refund on non-approved payment', function () {
    $this->payment->update(['status' => 'pending']);

    $client = Mockery::mock(MercadoPagoClient::class);
    $resolver = app(CredentialResolverInterface::class);
    $action = new CreateRefundAction($resolver, $client);

    expect(fn () => $action->execute($this->payment))
        ->toThrow(RuntimeException::class, 'Solo se pueden reembolsar pagos aprobados.');
});

it('rejects refund exceeding available amount', function () {
    $client = Mockery::mock(MercadoPagoClient::class);
    $resolver = app(CredentialResolverInterface::class);
    $action = new CreateRefundAction($resolver, $client);

    expect(fn () => $action->execute($this->payment, 999999.00))
        ->toThrow(RuntimeException::class, 'monto máximo');
});

it('updates to partially_refunded after partial refund', function () {
    $client = Mockery::mock(MercadoPagoClient::class);
    $client->shouldReceive('createRefund')
        ->with(55555, 300.00)
        ->andReturn(['id' => 1001, 'status' => 'approved']);

    $resolver = app(CredentialResolverInterface::class);
    $action = new CreateRefundAction($resolver, $client);

    $action->execute($this->payment, 300.00);

    $this->payment->refresh();

    expect($this->payment->status)->toBe('partially_refunded');
});
