<?php

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

it('creates a store', function () {
    $store = Store::create([
        'account_id' => $this->account->id,
        'mp_store_id' => 'MP_STORE_001',
        'name' => 'Sucursal Centro',
        'external_id' => 'store-001',
    ]);

    expect($store)->toBeInstanceOf(Store::class)
        ->and($store->name)->toBe('Sucursal Centro')
        ->and($store->external_id)->toBe('store-001')
        ->and($store->account_id)->toBe($this->account->id);
});

it('creates a pos terminal', function () {
    $store = Store::create([
        'account_id' => $this->account->id,
        'mp_store_id' => 'MP_STORE_002',
        'name' => 'Sucursal Norte',
    ]);

    $pos = PosTerminal::create([
        'account_id' => $this->account->id,
        'store_id' => $store->id,
        'mp_pos_id' => 'MP_POS_001',
        'name' => 'Caja 1',
        'fixed_amount' => false,
        'category' => 'PDV',
    ]);

    expect($pos)->toBeInstanceOf(PosTerminal::class)
        ->and($pos->name)->toBe('Caja 1')
        ->and($pos->store_id)->toBe($store->id)
        ->and($pos->fixed_amount)->toBeFalse()
        ->and($pos->category)->toBe('PDV');
});

it('has many pos terminals through store', function () {
    $store = Store::create([
        'account_id' => $this->account->id,
        'mp_store_id' => 'MP_STORE_003',
        'name' => 'Sucursal Sur',
    ]);

    PosTerminal::create([
        'account_id' => $this->account->id,
        'store_id' => $store->id,
        'mp_pos_id' => 'MP_POS_002',
        'name' => 'Caja 1',
    ]);

    PosTerminal::create([
        'account_id' => $this->account->id,
        'store_id' => $store->id,
        'mp_pos_id' => 'MP_POS_003',
        'name' => 'Caja 2',
    ]);

    expect($store->posTerminals)->toHaveCount(2);
});

it('belongs to a store from pos', function () {
    $store = Store::create([
        'account_id' => $this->account->id,
        'mp_store_id' => 'MP_STORE_004',
        'name' => 'Sucursal Este',
    ]);

    $pos = PosTerminal::create([
        'account_id' => $this->account->id,
        'store_id' => $store->id,
        'mp_pos_id' => 'MP_POS_004',
        'name' => 'Caja Única',
    ]);

    expect($pos->store)->toBeInstanceOf(Store::class)
        ->and($pos->store->name)->toBe('Sucursal Este');
});

it('deletes pos when account is deleted directly', function () {
    $store = Store::create([
        'account_id' => $this->account->id,
        'mp_store_id' => 'MP_STORE_005',
        'name' => 'Sucursal Temp',
    ]);

    PosTerminal::create([
        'account_id' => $this->account->id,
        'store_id' => $store->id,
        'mp_pos_id' => 'MP_POS_005',
        'name' => 'Caja Temp',
    ]);

    $this->account->delete();

    expect(Store::count())->toBe(0)
        ->and(PosTerminal::count())->toBe(0);
});
