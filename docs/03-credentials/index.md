# Application Credentials

## Overview

The Credentials page allows superadmins to configure the Mercado Pago application settings: `client_id`, `client_secret`, `webhook_secret`, `public_key`, `access_token`, and country.

All sensitive fields are stored encrypted via `spatie/laravel-settings`.

## Filament UI

**Settings → Mercado Pago → Credentials**

The page has two environment tabs:

- **Production**: client_id, client_secret, redirect_uri, webhook_secret, public_key, access_token, country
- **Sandbox**: public_key, access_token, country

### Mode toggle

Click **Production mode** / **Sandbox mode** in the section header to switch between environments. A confirmation modal appears before switching. The active mode is persisted automatically — no need to click Save after toggling.

### Save

Click **Save changes** to persist credential changes without switching modes.

## Public API

### `MercadoPagoApplicationSettings`

```php
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;

$settings = app(MercadoPagoApplicationSettings::class);

$settings->client_id;        // string
$settings->client_secret;    // encrypted string
$settings->public_key;       // string
$settings->access_token;     // encrypted string
$settings->redirect_uri;     // string
$settings->webhook_secret;   // encrypted string
$settings->country;          // string (MLA, MLB, MLC, MCO, MLM, MPE, MLU)
$settings->sandbox_mode;     // bool
```

### Country codes

| Code | Country | Auth domain |
|---|---|---|
| MLA | Argentina | `auth.mercadopago.com.ar` |
| MLB | Brazil | `auth.mercadopago.com.br` |
| MLC | Chile | `auth.mercadopago.cl` |
| MCO | Colombia | `auth.mercadopago.com.co` |
| MLM | Mexico | `auth.mercadopago.com.mx` |
| MPE | Peru | `auth.mercadopago.com.pe` |
| MLU | Uruguay | `auth.mercadopago.com.uy` |

## Notes

- Only users with `super_admin` role (Shield) or `viewAny` permission on `MercadoPagoAccount` can access this page.
- `client_secret`, `webhook_secret`, and `access_token` are stored encrypted.
- The `redirect_uri` is auto-generated from `config('app.url')`.
- All field labels and help texts are translatable.
