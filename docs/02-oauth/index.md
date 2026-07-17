# OAuth — Connect Account

## Overview

The OAuth flow connects a Mercado Pago account to your application. Tokens are stored encrypted and refreshed automatically.

## Filament UI

**Settings → Mercado Pago → Connect MP**

Shows connection status: **Connected**, **Disconnected**, or **Error**.

## Public API

### `GenerateAuthorizationUrlAction::execute()`

Generates the authorization URL to redirect the user to Mercado Pago.

```php
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\GenerateAuthorizationUrlAction;

$result = app(GenerateAuthorizationUrlAction::class)->execute();
// $result['url'] = 'https://auth.mercadopago.com.ar/authorization?...'
```

Uses PKCE with `code_challenge_method=S256`. The `state` parameter is encrypted with tenant info and `code_verifier`.

### `ExchangeCodeForTokenAction::execute()`

Exchanges the authorization code for tokens after the callback.

```php
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\ExchangeCodeForTokenAction;

app(ExchangeCodeForTokenAction::class)->execute(
    code: $code,
    codeVerifier: $codeVerifier,
    tenantId: $tenantId,
    tenantType: $tenantType,
);
```

Stores `access_token`, `refresh_token`, `public_key`, `scope`, `expires_at`, and sets `status=connected`.

### `DisconnectAccountAction::execute()`

Disconnects the account. Clears tokens but keeps the record for history.

```php
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\DisconnectAccountAction;

app(DisconnectAccountAction::class)->execute($account);
```

### `RefreshAccessTokenAction::execute()`

Refreshes an expiring token. Called automatically by the scheduler.

```php
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\RefreshAccessTokenAction;

app(RefreshAccessTokenAction::class)->execute($account);
```

### `RefreshExpiringTokensJob`

Scheduled job that refreshes tokens nearing expiration.

```php
// routes/console.php
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Jobs\RefreshExpiringTokensJob;

Schedule::job(new RefreshExpiringTokensJob)->daily();
```

## States

| Status | Description |
|---|---|
| `connected` | Tokens valid, account active |
| `disconnected` | User manually disconnected |
| `error` | Token expired or revoked by MP, reconnect needed |

## Notes

- Tokens are encrypted with Laravel's `encrypted` cast.
- When a token fails with 401, the account is automatically marked as `error`.
- The scheduler job checks `expires_at` against the threshold in config (`refresh_token_threshold_days`).
