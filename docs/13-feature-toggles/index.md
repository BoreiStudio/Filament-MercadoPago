# Feature Toggles

## Overview

The `MercadoPagoPlugin` class provides fluent methods to enable/disable features. Disabled features won't appear in the navigation.

## Available toggles

```php
use BoreiStudio\FilamentMercadoPago\MercadoPagoPlugin;

MercadoPagoPlugin::make()
    ->payments(true)      // Checkout Pro (default: true)
    ->refunds(true)       // Refunds (default: true, requires payments)
    ->point(false)        // Point devices (default: false)
    ->qr(false)           // QR codes (default: false)
    ->stores(false)       // Stores & POS (default: false, auto-enabled by point/qr)
    ->dashboard(true)     // Dashboard widget (default: true)
    ->documentation(true) // In-panel documentation (default: true)
    ->navigationGroup('Mercado Pago');
```

### Rules

- `point(true)` automatically enables `stores(true)`
- `qr(true)` automatically enables `stores(true)`
- `refunds` is only meaningful if `payments` is enabled

### Example: only payments

```php
MercadoPagoPlugin::make()
    ->payments()
    ->point(false)
    ->qr(false);
```

### Example: full suite

```php
MercadoPagoPlugin::make()
    ->payments()
    ->point()
    ->qr()
    ->stores();  // auto-enabled by point and qr
```

## How it works

The `registerFeatures()` method in `HasFeatureToggles` adds the appropriate resources, pages, and widgets to the Filament panel based on the toggle states. Navigation is handled by clusters and per-page `shouldRegisterNavigation()` settings.

## Programmatic checks

```php
$plugin = MercadoPagoPlugin::make();
$hasPayments = $plugin->payments();     // returns bool
$hasPoint = $plugin->point();           // returns bool
$hasQr = $plugin->qr();                 // returns bool
$hasStores = $plugin->stores();         // returns bool
```
