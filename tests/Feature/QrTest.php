<?php

use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Models\QrOrder;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;

beforeEach(function () {
    $this->account = MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'APP_USR-TEST',
        'public_key' => 'APP_PUB',
        'status' => 'connected',
    ]);

    $this->store = Store::create([
        'account_id' => $this->account->id,
        'mp_store_id' => 'MP_STORE_QR',
        'name' => 'Sucursal QR',
    ]);

    $this->pos = PosTerminal::create([
        'account_id' => $this->account->id,
        'store_id' => $this->store->id,
        'mp_pos_id' => 'MP_POS_QR',
        'name' => 'Caja QR',
        'external_id' => 'pos-qr-001',
        'qr_image_url' => 'https://example.com/qr/static.png',
    ]);
});

it('creates a qr order', function () {
    $order = QrOrder::create([
        'account_id' => $this->account->id,
        'pos_id' => $this->pos->id,
        'mp_order_id' => 'MP_ORDER_001',
        'external_reference' => 'qr-order-001',
        'title' => 'Pedido QR Test',
        'total_amount' => 1500.00,
        'items' => [['title' => 'Producto 1', 'quantity' => 1, 'unit_price' => 1500.00]],
        'status' => 'opened',
    ]);

    expect($order)->toBeInstanceOf(QrOrder::class)
        ->and($order->title)->toBe('Pedido QR Test')
        ->and((float) $order->total_amount)->toBe(1500.00)
        ->and($order->isOpened())->toBeTrue();
});

it('belongs to pos terminal', function () {
    $order = QrOrder::create([
        'account_id' => $this->account->id,
        'pos_id' => $this->pos->id,
        'mp_order_id' => 'MP_ORDER_002',
        'title' => 'Test',
        'total_amount' => 500.00,
        'status' => 'opened',
    ]);

    expect($order->pos)->toBeInstanceOf(PosTerminal::class)
        ->and($order->pos->name)->toBe('Caja QR');
});

it('closes a qr order', function () {
    $order = QrOrder::create([
        'account_id' => $this->account->id,
        'pos_id' => $this->pos->id,
        'mp_order_id' => 'MP_ORDER_003',
        'title' => 'Test close',
        'total_amount' => 300.00,
        'status' => 'opened',
    ]);

    expect($order->isOpened())->toBeTrue();

    $order->update(['status' => 'closed']);

    expect($order->fresh()->isClosed())->toBeTrue();
});

it('has static qr image url on pos', function () {
    expect($this->pos->qr_image_url)->toBe('https://example.com/qr/static.png');
});
