# Quick Start

Get from zero to a working payment in 5 minutes.

## 1. Install

```bash
composer require boreistudio/filament-mercadopago
php artisan migrate
```

## 2. Register the plugin

In your `PanelProvider`:

```php
use BoreiStudio\FilamentMercadoPago\MercadoPagoPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(MercadoPagoPlugin::make());
}
```

## 3. Configure application credentials

1. Go to **Settings → Mercado Pago → Credentials**
2. Enter your `Client ID` and `Client Secret` from the [Mercado Pago dashboard](https://www.mercadopago.com/developers)
3. Copy the **Redirect URI** and add it to your MP app's redirect URLs
4. Click **Save changes**

## 4. Connect your account

1. Go to **Settings → Mercado Pago → Connect MP**
2. Click **Connect with Mercado Pago**
3. Log in and authorize the app
4. You'll be redirected back — status should show **Connected**

## 5. Create a test payment

1. Go to **Mercado Pago → Payments**
2. Click **Nuevo pago**
3. Add a product (e.g. "Test product", quantity 1, price $100)
4. Click submit
5. You'll be redirected to the Mercado Pago checkout page

---

**Next steps:**
- Set up [webhooks](05-webhooks/) for automatic status updates
- Create [stores and POS](07-stores/) for in-person payments
- Configure [Point devices](09-point/) for physical terminals
- Enable [QR codes](10-qr/) for static and dynamic QR payments
