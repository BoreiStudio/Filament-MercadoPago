<?php

use BoreiStudio\FilamentMercadoPago\Features\Oauth\Controllers\OauthCallbackController;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Controllers\WebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/mercadopago/oauth/callback', [OauthCallbackController::class, '__invoke'])
    ->name('mercadopago.oauth.callback')
    ->middleware('web');

Route::post('/mercadopago/webhooks/{account?}', [WebhookController::class, '__invoke'])
    ->name('mercadopago.webhooks')
    ->withoutMiddleware(VerifyCsrfToken::class);
