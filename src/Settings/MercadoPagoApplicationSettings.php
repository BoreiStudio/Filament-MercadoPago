<?php

namespace BoreiStudio\FilamentMercadoPago\Settings;

use Spatie\LaravelSettings\Settings;

class MercadoPagoApplicationSettings extends Settings
{
    public ?string $client_id = '';

    public ?string $client_secret = '';

    public ?string $public_key = '';

    public ?string $access_token = '';

    public ?string $redirect_uri = '';

    public ?string $webhook_secret = '';

    public bool $sandbox_mode = false;

    public string $country = 'MLA';

    public static function group(): string
    {
        return 'mercadopago-app';
    }

    public static function encrypted(): array
    {
        return [
            'client_secret',
            'webhook_secret',
            'access_token',
        ];
    }
}
