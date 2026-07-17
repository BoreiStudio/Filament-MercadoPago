# API Reference

## Actions

### OAuth

| Action | Method | Description |
|---|---|---|
| `GenerateAuthorizationUrlAction` | `execute(): array` | Generates OAuth authorization URL with PKCE |
| `ExchangeCodeForTokenAction` | `execute(code, codeVerifier, tenantId?, tenantType?)` | Exchanges code for access/refresh tokens |
| `RefreshAccessTokenAction` | `execute(account)` | Refreshes an expiring access token |
| `DisconnectAccountAction` | `execute(account)` | Disconnects account, clears tokens |

### Payments

| Action | Method | Description |
|---|---|---|
| `CreatePreferenceAction` | `execute(items, externalReference, backUrls?, notificationUrl?, account?)` | Creates Checkout Pro preference |
| `SyncPaymentFromApiAction` | `execute(mpPaymentId)` | Syncs payment from MP API |

### Refunds

| Action | Method | Description |
|---|---|---|
| `CreateRefundAction` | `execute(payment, amount?)` | Processes full or partial refund |

### Stores

| Action | Method | Description |
|---|---|---|
| `SyncStoresFromApiAction` | `execute(): int` | Imports stores from MP |

### POS

| Action | Method | Description |
|---|---|---|
| `SyncPosFromApiAction` | `execute(): int` | Imports POS terminals from MP |

### Point

| Action | Method | Description |
|---|---|---|
| `CreatePaymentIntentAction` | `execute(device, amount, externalReference?)` | Creates payment intent on Point device |
| `CancelPaymentIntentAction` | `execute(device, paymentIntentId)` | Cancels a payment intent |

### QR

| Action | Method | Description |
|---|---|---|
| `CreateQrOrderAction` | `execute(pos, title, items, externalReference?)` | Creates dynamic QR order |
| `DeleteQrOrderAction` | `execute(qrOrder)` | Closes dynamic QR order |

## Models

| Model | Table | Key Fields |
|---|---|---|
| `MercadoPagoAccount` | `mercadopago_accounts` | `mp_user_id`, `access_token`, `refresh_token`, `status` |
| `Payment` | `mercadopago_payments` | `mp_payment_id`, `status`, `transaction_amount`, `source` |
| `Refund` | `mercadopago_refunds` | `payment_id`, `mp_refund_id`, `amount` |
| `Store` | `mercadopago_stores` | `mp_store_id`, `name`, `location` |
| `PosTerminal` | `mercadopago_pos` | `mp_pos_id`, `store_id`, `name`, `qr_image_url` |
| `PointDevice` | `mercadopago_point_devices` | `device_id`, `pos_id`, `operating_mode` |
| `QrOrder` | `mercadopago_qr_orders` | `mp_order_id`, `pos_id`, `status` |
| `WebhookEvent` | `mercadopago_webhook_events` | `mp_resource_id`, `signature_valid`, `status` |

## Policies

| Policy | Permissions |
|---|---|
| `MercadoPagoAccountPolicy` | viewAny, view, create, update, delete |
| `PaymentPolicy` | viewAny, view (no create/update/delete) |
| `RefundPolicy` | viewAny, view, create (no update/delete) |
| `StorePolicy` | CRUD complete |
| `PosTerminalPolicy` | CRUD complete |
| `PointDevicePolicy` | viewAny, view (no create/update/delete) |
| `QrOrderPolicy` | viewAny, view, create, delete (no update) |
| `WebhookEventPolicy` | viewAny, view (no create/update/delete) |

All policies are registered in `MercadoPagoServiceProvider` and compatible with Filament Shield.

## Contracts & Interfaces

| Interface | Implementation(s) |
|---|---|
| `CredentialResolverInterface` | `SingleTenantCredentialResolver`, `MultiTenantCredentialResolver` |
| `MercadoPagoCredentials` | `MercadoPagoCredentialsDTO` |

## Settings

| Class | Description |
|---|---|
| `MercadoPagoApplicationSettings` | `spatie/laravel-settings` for `client_id`, `client_secret`, `webhook_secret`, `country`, `sandbox_mode` |

## Exceptions

| Exception | Thrown when |
|---|---|
| `MercadoPagoAccountNotConnectedException` | No account connected or disconnected |
| `MercadoPagoException` | SDK call fails |
