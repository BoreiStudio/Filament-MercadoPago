# Troubleshooting & FAQ

## Common errors

### "Array to string conversion" when creating POS

**Cause:** The `qr` field in MP's response is an object, not a string. Fixed by accessing `$mpPos['qr']['image']`.

**Fix:** Make sure you're using the latest version of the plugin.

### "category must be number" when creating POS

**Cause:** The category value was sent as a string. MP expects a numeric MCC code.

**Fix:** Cast to `(int)` before sending, or leave it empty to use the generic category.

### "external_id must be alphanumeric"

**Cause:** The `external_id` contained hyphens or special characters.

**Fix:** Use only letters and numbers (validated by `->rule('alpha_num')` in the form).

### "external id is already assigned to this user"

**Cause:** The `external_id` must be unique per Mercado Pago account.

**Fix:** Use a different `external_id`, or delete the existing POS/store before reusing it.

### "The registered store id does not match with the store id"

**Cause:** The `external_store_id` sent doesn't match the store's actual `external_id` in MP.

**Fix:** Use the store's `external_id` from its MP `raw_payload`, not from the local database (which may be null).

### 405 Method Not Allowed on cancel payment

**Cause:** The `mp_payment_id` is null (preference was created but never paid). The plugin was trying to hit `/v1/payments/` with an empty ID.

**Fix:** Updated to only call the API when `mp_payment_id` is present.

### Map error: "mpMapPicker is not defined"

**Cause:** The Alpine data component wasn't available when the ViewField rendered inside a modal.

**Fix:** Define Alpine data inline in `x-data` instead of referencing an external function, and load Leaflet via `FilamentAsset::register()`.

### Map error: "Map container is already initialized"

**Cause:** Livewire re-renders trigger a second initialization.

**Fix:** Added `if (this.map) return;` guard in the `init()` method.

### "Translation not found" for plugin strings

**Cause:** The locale cache might be stale.

**Fix:**
```bash
php artisan optimize:clear
```

Or set the locale in Filament's PanelProvider:
```php
return $panel
    ->locale('es');  // or 'pt_BR'
```

## Frequently asked questions

### Do I need a Point terminal to test Point orders?

Yes. Point orders are sent to physical Point terminals in PDV mode. In sandbox, you can create test devices.

### Can I use this plugin without Filament?

No. The plugin is built specifically for Filament v5 panels and depends on Filament's UI components, navigation, and form system.

### How do I correlate payments with my orders?

Use the `external_reference` field. When creating a preference, pass your own entity ID:
```php
'externalReference' => (string) $order->id,
```

### Are refunds processed synchronously?

Yes. The refund API call is synchronous. The local payment status is updated immediately after a successful API response.

### How do I handle expired/revoked tokens?

The `RefreshExpiringTokensJob` automatically refreshes tokens before they expire. If a token is revoked by the user in MP, the account status is set to `error` on the next API call. Go to **Settings → Connect MP** and click **Reconnect**.

### Can I customize the navigation group name?

Yes:
```php
MercadoPagoPlugin::make()
    ->navigationGroup('My Custom Group');
```

### Do I need to publish assets?

The plugin's CSS is auto-published via `php artisan filament:assets`. If you modify the plugin's Tailwind styles, run:
```bash
cd vendor/boreistudio/filament-mercadopago
npm install && npm run build
php artisan filament:assets
```
