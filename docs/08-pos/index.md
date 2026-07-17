# POS Terminals (Cajas)

## Overview

POS terminals (cash registers) belong to a store. Each POS has a unique external ID, a category (MCC code), and a static QR image for QR payments.

## Filament UI

**Mercado Pago → POS**

- Table with columns: name, store, external_id, MP POS ID, fixed amount, category
- **Nueva caja**: create with store selector, name, external ID, category, fixed amount toggle
- **Edit**: update all fields
- **Delete**: removes from MP and locally
- **Sincronizar**: imports POS from MP
- **Ver QR**: opens the static QR image in a new tab

## Public API

### POS CRUD

**Create endpoint:** `POST /pos`
**Update endpoint:** `PUT /pos/{id}`
**Delete endpoint:** `DELETE /pos/{id}`
**Sync endpoint:** `GET /pos`

### `SyncPosFromApiAction`

```php
use BoreiStudio\FilamentMercadoPago\Features\Pos\Actions\SyncPosFromApiAction;

$count = app(SyncPosFromApiAction::class)->execute();
```

## Models

### `PosTerminal`

| Field | Type | Description |
|---|---|---|
| `store_id` | `foreignId` | Related store |
| `mp_pos_id` | `string?` | Mercado Pago POS ID (unique) |
| `name` | `string` | POS name |
| `external_id` | `string?` | External ID (alphanumeric, max 40 chars) |
| `fixed_amount` | `bool` | Whether amount is fixed by seller |
| `category` | `string?` | MCC code (e.g. `621102` for Gastronomy) |
| `qr_image_url` | `string?` | Static QR image URL |

**Relations:** `belongsTo(Store)`, `hasMany(QrOrder)`

## Notes

- The `external_id` must be alphanumeric (no hyphens or underscores) and unique per user.
- MCC codes vary by country. Common codes: `621102` (Gastronomy).
- The static QR image is generated automatically by MP when the POS is created.
- POS with `pos(false)` in the plugin config will not register this feature.
