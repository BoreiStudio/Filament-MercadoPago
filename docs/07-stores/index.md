# Stores (Sucursales)

## Overview

Stores represent physical branches. They are a prerequisite for POS terminals, Point devices, and QR codes. The plugin syncs stores with Mercado Pago.

## Filament UI

**Mercado Pago → Stores**

- Table with columns: name, external_id, MP store ID, POS count, created date
- **Nueva sucursal**: create with name, external ID, and location (street, city, province, coordinates)
- **Edit**: update name, external ID, and location
- **Delete**: removes from MP and locally
- **Sincronizar**: imports stores from MP that were created externally

## Public API

### Store CRUD

Stores are managed through the Filament UI resources. The API syncs automatically on create/edit/delete.

**Create endpoint:** `POST /users/{user_id}/stores`
**Update endpoint:** `PUT /users/{user_id}/stores/{store_id}`
**Delete endpoint:** `DELETE /users/{user_id}/stores/{store_id}`
**Sync endpoint:** `GET /users/{user_id}/stores`

### `SyncStoresFromApiAction`

```php
use BoreiStudio\FilamentMercadoPago\Features\Stores\Actions\SyncStoresFromApiAction;

$count = app(SyncStoresFromApiAction::class)->execute();
```

## Models

### `Store`

| Field | Type | Description |
|---|---|---|
| `mp_store_id` | `string` | Mercado Pago store ID (unique) |
| `name` | `string` | Store name |
| `external_id` | `string?` | Your external/store ID |
| `business_hours` | `json?` | Operating hours |
| `location` | `json?` | Street, city, coordinates |
| `raw_payload` | `json?` | Full MP response |

**Relations:** `hasMany(PosTerminal)`

## Map Picker

The create/edit forms include an OpenStreetMap map picker (Leaflet) for selecting the store's geographic coordinates. Click on the map to place a marker.

## Notes

- Location fields (`city_name`, `state_name`, `latitude`, `longitude`) are required by MP.
- Province names are resolved via the Mercado Libre API (`api.mercadolibre.com/classified_locations`).
- POS terminals cannot be created without at least one store.
- Stores with `stores(false)` in the plugin config will not register this feature.
