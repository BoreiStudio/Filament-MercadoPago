<?php

return [
    'mode' => env('MERCADOPAGO_MODE', 'single_tenant'),

    'tenant_model' => null,

    'oauth' => [
        'redirect_uri' => env('APP_URL').'/mercadopago/oauth/callback',
        'scopes' => ['read', 'write', 'offline_access'],
    ],

    'webhooks' => [
        'route_prefix' => 'mercadopago/webhooks',
        'verify_signature' => true,
    ],

    'sync' => [
        'refresh_token_threshold_days' => 15,
    ],
];
