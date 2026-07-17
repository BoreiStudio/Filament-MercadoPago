# Checkout Pro

## Overview

Creates payment preferences and redirects customers to Mercado Pago's hosted checkout. Payments are tracked locally via webhooks.

## Filament UI

**Mercado Pago → Payments**

- Table with filters by status and date
- **Nuevo pago** action to create a preference (for testing)
- View modal with full payment details and JSON payload
- **Resincronizar** to fetch latest status from MP
- **Reembolsar** for approved payments

## Public API

### `CreatePreferenceAction::execute()`

```php
use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\CreatePreferenceAction;

$result = app(CreatePreferenceAction::class)->execute(
    items: [
        ['title' => 'Producto 1', 'quantity' => 1, 'unit_price' => 1500.00],
    ],
    externalReference: 'order-123',
    backUrls: [
        'success' => route('checkout.success'),
        'failure' => route('checkout.failure'),
        'pending' => route('checkout.pending'),
    ],
    notificationUrl: route('mercadopago.webhooks'),
    account: $account,  // optional, for multi-tenant
);
```

**Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `items` | `array` | ✅ | Array with `title`, `quantity`, `unit_price` |
| `externalReference` | `string` | ✅ | Your order/receipt ID for correlation |
| `backUrls` | `array` | ❌ | Return URLs after payment |
| `notificationUrl` | `string` | ❌ | Webhook URL for status updates |
| `account` | `MercadoPagoAccount` | ❌ | Specific account (multi-tenant) |

**Returns:**
```php
[
    'init_point' => 'https://www.mercadopago.com/...',
    'sandbox_init_point' => 'https://sandbox.mercadopago.com/...',
    'payment' => Payment::class,  // local record
]
```

### Usage from business logic

```php
$result = app(CreatePreferenceAction::class)->execute(
    items: $cart->items()->map(fn ($item) => [
        'title' => $item->name,
        'quantity' => $item->quantity,
        'unit_price' => $item->price,
    ])->toArray(),
    externalReference: (string) $order->id,
    backUrls: [
        'success' => route('orders.success', $order),
        'failure' => route('orders.failure', $order),
    ],
    notificationUrl: route('mercadopago.webhooks'),
);

return redirect()->away($result['init_point']);
```

### `SyncPaymentFromApiAction::execute()`

```php
use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\SyncPaymentFromApiAction;

app(SyncPaymentFromApiAction::class)->execute(123456789); // mp_payment_id
```

Fetches `GET /v1/payments/{id}` and upserts the local record.

## Models

### `Payment`

| Field | Type | Description |
|---|---|---|
| `mp_payment_id` | `string` | Mercado Pago payment ID (unique) |
| `preference_id` | `string?` | Checkout Pro preference ID |
| `status` | `string` | approved, pending, rejected, refunded, etc. |
| `transaction_amount` | `decimal` | Payment amount |
| `payment_method_id` | `string?` | e.g. `visa`, `master` |
| `payer_email` | `string?` | Buyer email |
| `external_reference` | `string?` | Your correlation ID |
| `source` | `string` | checkout_pro, point, qr |

**Helpers:** `isApproved()`, `isPending()`, `isRejected()`, `isRefunded()`, `isPartiallyRefunded()`, `statusColor()`

## Notes

- `external_reference` is your key for correlating with your business entities.
- Webhooks automatically sync payment status changes.
- The preference expiration defaults to the MP configured value.
