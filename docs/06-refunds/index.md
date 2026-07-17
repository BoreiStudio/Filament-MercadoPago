# Refunds

## Overview

Process full or partial refunds on approved payments. The plugin validates amounts server-side before calling the MP API.

## Filament UI

Open an approved payment's detail view and click **Reembolsar**:

- **Total refund**: leave amount empty
- **Partial refund**: enter the desired amount
- The form shows the maximum available amount (original minus previous refunds)

## Public API

### `CreateRefundAction::execute()`

```php
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Actions\CreateRefundAction;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;

$payment = Payment::find(1);

// Full refund
app(CreateRefundAction::class)->execute($payment);

// Partial refund
app(CreateRefundAction::class)->execute($payment, 250.00);
```

**Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `payment` | `Payment` | ✅ | The approved payment model |
| `amount` | `float?` | ❌ | Amount to refund (null = full) |

**Throws:** `RuntimeException` with validation messages.

### Validations (server-side)

| Condition | Result |
|---|---|
| Payment not approved | `RuntimeException` |
| Already fully refunded | `RuntimeException` |
| Amount exceeds available balance | `RuntimeException` with detail |
| Amount is null | Full refund of remaining balance |

After a successful refund, the payment status updates to `refunded` or `partially_refunded`.

## Models

### `Refund`

| Field | Type | Description |
|---|---|---|
| `payment_id` | `foreignId` | Related payment |
| `mp_refund_id` | `string?` | MP refund ID |
| `amount` | `decimal` | Refunded amount |
| `status` | `string` | `approved` |
| `raw_payload` | `json` | Full MP response |

## Notes

- Refunds can only be processed on payments with `status = approved`.
- The available amount is calculated server-side: `transaction_amount - SUM(previous refunds)`.
- Refunds created outside the panel (e.g., MP dashboard) are synced via webhooks.
