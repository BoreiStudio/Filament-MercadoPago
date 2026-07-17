<?php

use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;

beforeEach(function () {
    $this->account = MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'APP_USR-TEST',
        'public_key' => 'APP_PUB',
        'status' => 'connected',
    ]);
});

it('creates a point device', function () {
    $device = PointDevice::create([
        'account_id' => $this->account->id,
        'device_id' => 'POINT_DEVICE_001',
        'model' => 'Point Smart',
        'operating_mode' => 'PDV',
        'status' => 'active',
    ]);

    expect($device)->toBeInstanceOf(PointDevice::class)
        ->and($device->device_id)->toBe('POINT_DEVICE_001')
        ->and($device->model)->toBe('Point Smart')
        ->and($device->operating_mode)->toBe('PDV')
        ->and($device->status)->toBe('active');
});

it('belongs to a pos terminal', function () {
    $store = Store::create([
        'account_id' => $this->account->id,
        'mp_store_id' => 'MP_STORE_POINT',
        'name' => 'Sucursal Point',
    ]);

    $pos = PosTerminal::create([
        'account_id' => $this->account->id,
        'store_id' => $store->id,
        'mp_pos_id' => 'MP_POS_POINT',
        'name' => 'Caja Point',
    ]);

    $device = PointDevice::create([
        'account_id' => $this->account->id,
        'pos_id' => $pos->id,
        'device_id' => 'POINT_DEVICE_002',
        'model' => 'Point Pro',
        'status' => 'active',
    ]);

    expect($device->pos)->toBeInstanceOf(PosTerminal::class)
        ->and($device->pos->name)->toBe('Caja Point');
});

it('updates device pos association', function () {
    $device = PointDevice::create([
        'account_id' => $this->account->id,
        'device_id' => 'POINT_DEVICE_003',
        'status' => 'active',
    ]);

    $device->update(['operating_mode' => 'STANDALONE']);

    expect($device->fresh()->operating_mode)->toBe('STANDALONE');
});

it('belongs to account', function () {
    $device = PointDevice::create([
        'account_id' => $this->account->id,
        'device_id' => 'POINT_DEVICE_004',
        'status' => 'active',
    ]);

    expect($device->account)->toBeInstanceOf(MercadoPagoAccount::class)
        ->and($device->account->id)->toBe($this->account->id);
});
