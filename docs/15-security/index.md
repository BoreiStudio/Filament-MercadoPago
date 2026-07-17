# Security

## Overview

The plugin follows security best practices:

- All tokens are encrypted at rest
- Webhook notifications are cryptographically verified
- Refund amounts are validated server-side
- No sensitive data is logged

## Token encryption

All sensitive credentials are stored using Laravel's `encrypted` Eloquent cast:

- `MercadoPagoAccount.access_token`
- `MercadoPagoAccount.refresh_token`
- `MercadoPagoApplicationSettings.client_secret`
- `MercadoPagoApplicationSettings.webhook_secret`
- `MercadoPagoApplicationSettings.access_token`

```php
protected function casts(): array
{
    return [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];
}
```

## Webhook signature validation

Every webhook request is validated using HMAC-SHA256 before any data is processed:

1. MP sends `x-signature` header with `ts` (timestamp) and `v1` (hash)
2. The plugin computes `HMAC-SHA256(id;request-id;ts;)` using the `webhook_secret`
3. If the hash doesn't match, a `401` is returned and the event is logged (but not processed)
4. Invalid events are stored for security auditing

## Server-side amount validation

Refund amounts are validated before calling the MP API:

```php
// Validates: payment is approved, available > 0, amount <= available
// All in CreateRefundAction::execute() before API call
```

## Logging policy

- No `access_token`, `refresh_token`, or `client_secret` is ever logged
- No `dd()`, `dump()`, `var_dump()`, or `print_r()` in production code
- All `logger()` calls have been removed from the source

## Authorization

- Settings page: only `super_admin` (Shield) or users with `viewAny` on `MercadoPagoAccount`
- Documentation page: same access policy as settings
- All models have Filament Shield-compatible policies
- Bulk actions use `->authorizeIndividualRecords()` for policy-gated operations

## Tailwind CSS build

The plugin ships with its own Tailwind CSS build including the `@tailwindcss/typography` plugin for prose styling. If you customize the plugin's CSS:

```bash
cd vendor/boreistudio/filament-mercadopago
npm install && npm run build
php artisan filament:assets
```

## Reporting vulnerabilities

See [SECURITY.md](../SECURITY.md) for instructions on reporting vulnerabilities.
