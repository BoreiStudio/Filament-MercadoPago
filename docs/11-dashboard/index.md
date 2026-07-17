# Dashboard

## Overview

The plugin provides a stats widget and cluster navigation for the Mercado Pago panel.

## Stats Widget

The `MercadoPagoStatsWidget` shows payment summaries on the Filament dashboard:

- **Today**: total amount and count by source (checkout_pro, point, qr)
- **This week**: same breakdown
- **This month**: same breakdown

```php
use BoreiStudio\FilamentMercadoPago\Features\Dashboard\Widgets\MercadoPagoStatsWidget;
```

The widget is registered automatically in the panel.

## Cluster Navigation

The `MercadoPagoCluster` groups all payment-related pages under a **Mercado Pago** navigation group with horizontal tabs.

### Connection badge

A badge appears next to the navigation item:

| Status | Badge | Color |
|---|---|---|
| Connected | `✓` | `success` (green) |
| Disconnected | `!` | `warning` (yellow) |
| Error / No account | `!` | `danger` (red) |

### Config cluster

The **Settings → Mercado Pago** cluster groups:

- **Credentials**: application settings
- **Connect MP**: OAuth account connection

## Access control

The widget uses `canView()` with the same policy as the settings pages:

- Users with `super_admin` role (Shield) can always view it.
- Users with `viewAny` permission on `MercadoPagoAccount` can also view it.
- Can be disabled via `->dashboard(false)` in the plugin config.

## Customizing navigation

```php
MercadoPagoPlugin::make()
    ->navigationGroup('Mi Grupo')  // custom group name
```
