# Multi-Tenant

## Overview

The plugin supports multi-tenant Filament panels. Each tenant connects their own Mercado Pago account via OAuth. The correct account is resolved automatically based on the current tenant.

## How it works

The `CredentialResolverInterface` has two implementations:

- **SingleTenantCredentialResolver**: resolves the single account (where `tenant_id IS NULL`)
- **MultiTenantCredentialResolver**: resolves by `Filament::getTenant()`

The binding is selected automatically:

```php
// In MercadoPagoServiceProvider::packageRegistered()
if ($panel->hasTenancy()) {
    // Use MultiTenantCredentialResolver
} else {
    // Use SingleTenantCredentialResolver
}
```

You can also force a mode via `.env`:

```
MERCADOPAGO_MODE=multi_tenant
```

## Tenant account model

```php
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;

// Scoped to current tenant
$account = MercadoPagoAccount::query()
    ->where('tenant_id', $tenantId)
    ->where('tenant_type', $tenantType)
    ->first();
```

The `mercadopago_accounts` table has `tenant_id` (nullable morphs) for multi-tenant support.

## Form selects in multi-tenant

Form selects that list entities (stores, POS) are automatically scoped to the tenant via `modifyQueryUsing`. No manual scoping is needed.

## Credential Resolver

```php
use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;

$credentials = app(CredentialResolverInterface::class)->resolve();
// Returns MercadoPagoCredentialsDTO with:
// - getAccessToken()
// - getPublicKey()
// - getMpUserId()
// - isLiveMode()
```

## Webhook routing

In multi-tenant mode, the webhook URL can include the account identifier:

```
POST /mercadopago/webhooks/{account?}
```

This helps route the webhook to the correct tenant without relying solely on the payload.

## Notes

- Each tenant must connect their own MP account via OAuth.
- The OAuth `state` parameter includes `tenant_id` and `tenant_type` to match the callback.
- If no tenant context is available, the resolver throws `MercadoPagoAccountNotConnectedException`.
