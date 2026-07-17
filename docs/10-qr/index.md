# QR Codes

## Overview

QR codes allow customers to pay by scanning a code. The plugin supports both static and dynamic QR modes.

### Static QR

Each POS terminal has a **static QR image** (`PosTerminal.qr_image_url`) generated automatically by MP upon creation. The customer scans it and enters the amount themselves.

### Dynamic QR

A **dynamic QR** is created for a specific amount and items. It expires after payment or can be closed manually.

## Filament UI

**Mercado Pago → QR Codes**

1. Select a POS terminal from the dropdown
2. View the static QR image
3. **Generar QR dinámico**: enter title, amount, and items for a one-time QR
4. **Cerrar orden**: close an open dynamic QR order

**From POS Terminals page:**
- **Ver QR** action: opens the static QR image

## Public API

### `CreateQrOrderAction::execute()`

```php
use BoreiStudio\FilamentMercadoPago\Features\Qr\Actions\CreateQrOrderAction;

$result = app(CreateQrOrderAction::class)->execute(
    pos: $posTerminal,
    title: 'Product purchase',
    items: [
        ['title' => 'Product 1', 'quantity' => 1, 'unit_price' => 500.00],
    ],
    externalReference: 'qr-order-123',
);
```

Creates a dynamic QR order via `PUT /instore/orders/qr/seller/collectors/{user_id}/pos/{external_pos_id}/qrs`.

### `DeleteQrOrderAction::execute()`

```php
use BoreiStudio\FilamentMercadoPago\Features\Qr\Actions\DeleteQrOrderAction;

app(DeleteQrOrderAction::class)->execute($qrOrder);
```

Closes/cancels an open dynamic QR order.

## Models

### `QrOrder`

| Field | Type | Description |
|---|---|---|
| `pos_id` | `foreignId` | Related POS terminal |
| `mp_order_id` | `string` | Mercado Pago order ID (unique) |
| `external_reference` | `string?` | Your order ID |
| `title` | `string?` | Order title |
| `total_amount` | `decimal` | Total amount |
| `items` | `json` | Line items |
| `status` | `string` | `opened` or `closed` |

**Relations:** `belongsTo(PosTerminal)`

**Helpers:** `isOpened()`, `isClosed()`

## Notes

- QR codes require stores and POS to be configured (`stores(true)`).
- Webhooks notify payment status changes via `topic=merchant_order`.
- The static QR image URL is available on the POS record after creation.
