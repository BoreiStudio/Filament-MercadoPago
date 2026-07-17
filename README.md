# Filament MercadoPago Plugin

A Filament v5 plugin that integrates Mercado Pago payments into your Laravel application. Supports Checkout Pro (online payments), Point (in-person terminal payments), QR codes, OAuth account connection, webhooks, refunds, and in-panel documentation.

## Requirements

- PHP 8.2+
- Laravel 11+ / 12+
- Filament 5.x

## Installation

```bash
composer require boreistudio/filament-mercadopago
php artisan migrate
```

Register the plugin in your `PanelProvider`:

```php
use BoreiStudio\FilamentMercadoPago\MercadoPagoPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(MercadoPagoPlugin::make());
}
```

## Configuration

A **Settings → Mercado Pago → Credentials** page appears in the Filament panel for superadmins to configure the application credentials (`client_id`, `client_secret`, `webhook_secret`, country). All sensitive values are stored encrypted.

The plugin supports two modes:

- **Single-tenant**: one MP account for the whole app.
- **Multi-tenant**: each tenant connects their own MP account via OAuth.

The mode is auto-detected from your panel's tenancy configuration.

## OAuth — Connect your Mercado Pago account

Navigate to **Settings → Mercado Pago → Connect MP** and click **Connect with Mercado Pago**. After authorizing, tokens are stored encrypted in the database. Tokens are refreshed automatically before expiration.

## Feature Toggles

```php
MercadoPagoPlugin::make()
    ->payments()           // Checkout Pro (default: true)
    ->point(false)         // Point terminals (default: false)
    ->qr(false)            // QR codes (default: false)
    ->stores(false)        // Stores & POS (auto-enabled by point/qr)
    ->dashboard(true)      // Dashboard widget (default: true)
    ->documentation(true)  // In-panel documentation (default: true)
    ->navigationGroup('Mercado Pago');
```

## Documentation

Full documentation is available in the `docs/` directory and also accessible from the Filament panel at **Settings → Mercado Pago → Documentation**.

## Security

- All tokens (`access_token`, `refresh_token`, `client_secret`) are stored encrypted in the database.
- Webhook notifications are validated via HMAC-SHA256 signature before processing.
- Refund amounts are validated server-side before calling the MP API.
- No sensitive data is logged.
- See `SECURITY.md` for reporting vulnerabilities.

## Testing

```bash
./vendor/bin/pest
```

Integration tests against the Mercado Pago sandbox are available under the `sandbox` group:

```bash
MERCADOPAGO_SANDBOX_ACCESS_TOKEN=TEST-... ./vendor/bin/pest --group=sandbox
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
