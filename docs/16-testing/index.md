# Testing

## Overview

The plugin includes 42 unit tests (with HTTP mocks) and optional integration tests against the MP sandbox.

## Running tests

```bash
cd vendor/boreistudio/filament-mercadopago
./vendor/bin/pest
```

This runs all feature tests with mocked HTTP responses.

## Test structure

```
tests/
  Feature/
    MercadoPagoClientTest.php              → SDK wrapper
    SingleTenantCredentialResolverTest.php → Credential resolution
    MultiTenantCredentialResolverTest.php  → Multi-tenant resolution
    OAuthFlowTest.php                      → Authorization, token exchange, refresh
    PaymentsTest.php                       → Preference creation, sync
    WebhooksTest.php                       → Signature validation, event processing
    RefundsTest.php                        → Full/partial refunds, validations
    StoresPosTest.php                      → Store and POS CRUD, relations
    PointTest.php                          → Point device CRUD, relations
    QrTest.php                             → QR order creation, closure
  Integration/
    SandboxPaymentTest.php                 → Real MP sandbox (requires token)
```

## Sandbox integration tests

These tests run against the real Mercado Pago sandbox and require a valid access token:

```bash
MERCADOPAGO_SANDBOX_ACCESS_TOKEN=TEST-xxx ./vendor/bin/pest --group=sandbox
```

They are skipped by default (excluded in `phpunit.xml`).

### Available tests

| Test | Description |
|---|---|
| Create Checkout Pro preference | Creates a real preference in sandbox |
| Fetch payment by ID | Requires `MERCADOPAGO_SANDBOX_PAYMENT_ID` environment variable |

## Writing tests

For Filament page tests:

```php
use function Pest\Livewire\livewire;

it('can list payments', function () {
    livewire(PaymentsPage::class)
        ->assertCanSeeTableRecords($payments);
});
```

For multi-tenant tests:

```php
Filament::setTenant($team);
Filament::setCurrentPanel('admin');
```

For HTTP mock tests:

```php
Http::fake([
    'api.mercadopago.com/*' => Http::response(['id' => '12345']),
]);
```
