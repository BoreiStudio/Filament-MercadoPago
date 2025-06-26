<?php

use BoreiStudio\FilamentMercadoPago\Http\Controllers\ConnectController;

Route::middleware(['web', 'auth'])
    ->prefix('mercado-pago')
    ->name('mercadopago.')
    ->group(function () {
        Route::get('connect', [ConnectController::class, 'redirect'])->name('connect');
        Route::get('callback', [ConnectController::class, 'callback'])->name('callback');
    });