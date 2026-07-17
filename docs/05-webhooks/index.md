# Webhooks

## Overview

Mercado Pago sends HTTP POST notifications to your webhook endpoint when payment or point events occur. The plugin validates the HMAC-SHA256 signature before processing.

## Configuration

1. Set your webhook URL in the Mercado Pago dashboard:
   ```
   https://yourdomain.com/mercadopago/webhooks
   ```

2. Set the **Webhook Secret** in **Settings → Mercado Pago → Credentials**.

The webhook route is automatically excluded from CSRF protection.

## Flow

1. MP sends `POST /mercadopago/webhooks` with `x-signature` and `x-request-id` headers
2. `SignatureValidator` verifies the HMAC-SHA256 against the `webhook_secret`
3. If invalid → `401`, but the event is still persisted (audit log)
4. If valid → event is saved, `ProcessPaymentWebhookJob` (or `ProcessPointWebhookJob`) is dispatched
5. The job calls `SyncPaymentFromApiAction` and marks the event as `processed` or `error`

## Filament UI

**Mercado Pago → Webhook Events**

- Table with filters by status, signature validity, and date
- Badge for signature (valid/invalid) and status (pending/processed/error)
- View modal with full JSON payload
- **Reprocess** button to re-dispatch failed events

## Public API

### `SignatureValidator::validate()`

```php
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Support\SignatureValidator;

$valid = SignatureValidator::validate(
    xSignature: $request->header('x-signature'),
    xRequestId: $request->header('x-request-id'),
    dataId: $request->query('data.id'),
    secret: $webhookSecret,
);
```

### `ProcessPaymentWebhookJob`

Handles `type=payment` and `type=merchant_order` topics. Retries 3 times on failure.

### `ProcessPointWebhookJob`

Handles `topic=point_integration_wh`. Queries the payment intent status and creates a local `Payment` record.

## Models

### `WebhookEvent`

| Field | Type | Description |
|---|---|---|
| `mp_resource_id` | `string?` | ID of the MP resource |
| `topic` | `string?` | Event topic |
| `signature_valid` | `bool` | Whether HMAC validation passed |
| `status` | `string` | pending, processed, error |
| `raw_payload` | `longText` | Full request body |

## Notes

- The webhook endpoint accepts an optional `{account}` segment in the URL for multi-tenant routing.
- Failed jobs remain in the queue for manual retry via the panel.
- Events with invalid signatures are still stored for security auditing.
