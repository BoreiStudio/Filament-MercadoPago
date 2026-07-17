# Point Devices

## Overview

Point devices are physical payment terminals (Point Smart, Point Pro) that process in-person card payments. Each device must be associated with a POS terminal and configured in PDV mode.

## Filament UI

**Mercado Pago → Point Devices**

- Table: device ID, model, operating mode, status, associated POS
- **View**: detail modal with device info
- **Cobrar**: create a payment order sent to the terminal
- **Vincular a Caja** (planned): associate device with a POS terminal

**From POS Terminals page:**
- **Cobrar** button in toolbar: select a Point device, enter amount, create order

## Public API

### `POST /v1/orders` — Create Point Order

```php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$response = Http::withToken($accessToken)
    ->withHeader('X-Idempotency-Key', (string) Str::uuid())
    ->post('https://api.mercadopago.com/v1/orders', [
        'type' => 'point',
        'external_reference' => 'order-123',
        'expiration_time' => 'PT15M',
        'transactions' => [
            'payments' => [
                ['amount' => '50.00'],
            ],
        ],
        'config' => [
            'point' => [
                'terminal_id' => $device->device_id,
                'print_on_terminal' => 'no_ticket', // no_ticket, partial_ticket, full_ticket
            ],
        ],
        'description' => 'Smartphone',
    ]);
```

### `CreatePaymentIntentAction::execute()`

```php
use BoreiStudio\FilamentMercadoPago\Features\Point\Actions\CreatePaymentIntentAction;

$result = app(CreatePaymentIntentAction::class)->execute(
    device: $pointDevice,
    amount: 1500.00,
    externalReference: 'order-456',
);
```

### `CancelPaymentIntentAction::execute()`

```php
use BoreiStudio\FilamentMercadoPago\Features\Point\Actions\CancelPaymentIntentAction;

app(CancelPaymentIntentAction::class)->execute(
    device: $pointDevice,
    paymentIntentId: $paymentIntentId,
);
```

## Models

### `PointDevice`

| Field | Type | Description |
|---|---|---|
| `pos_id` | `foreignId?` | Associated POS terminal |
| `device_id` | `string` | Physical terminal ID (unique, e.g. `NEWLAND_N950__...`) |
| `model` | `string?` | Device model |
| `operating_mode` | `string?` | `PDV`, `STANDALONE`, `UNDEFINED` |
| `status` | `string?` | Device status |

**Relations:** `belongsTo(PosTerminal)`

## Notes

- The terminal must be in PDV mode (configured via MP mobile app or API) to accept orders.
- `X-Idempotency-Key` is required to prevent duplicate orders.
- Orders expire after 15 minutes by default.
- Only one `payment` transaction per Point order.
- Point webhooks (`topic=point_integration_wh`) are handled by `ProcessPointWebhookJob`.
