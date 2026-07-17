# Installation & Configuration

## Install

```bash
composer require boreistudio/filament-mercadopago
php artisan migrate
```

## Register the plugin

In your `PanelProvider`:

```php
use BoreiStudio\FilamentMercadoPago\MercadoPagoPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(MercadoPagoPlugin::make());
}
```

## CSS Build

The plugin ships with pre-compiled CSS. If you modify the Tailwind styles:

```bash
cd vendor/boreistudio/filament-mercadopago
npm install
npm run build
php artisan filament:assets
```

## Configuration

### Mode: single-tenant vs multi-tenant

The plugin auto-detects the mode from your panel's tenancy configuration.

- **Single-tenant**: one MP account for the whole app.
- **Multi-tenant**: each tenant connects their own MP account via OAuth.

You can override in `.env`:

```
MERCADOPAGO_MODE=single_tenant
```

### Application credentials

After installation, go to **Settings → Mercado Pago → Credentials** in the Filament panel.

| Field | Description |
|---|---|
| Client ID | From your Mercado Pago application |
| Client Secret | From your Mercado Pago application |
| Public Key | From your Mercado Pago dashboard |
| Access Token | From your Mercado Pago dashboard |
| Redirect URI | Auto-generated, copy into your MP app settings |
| Webhook Secret | Generate in your MP app webhook settings |
| Country | Determines the authentication domain |

### Webhook URL

Configure in your Mercado Pago application dashboard:

```
https://yourdomain.com/mercadopago/webhooks
```

The webhook route is automatically excluded from CSRF protection.
