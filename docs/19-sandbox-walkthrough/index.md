# Sandbox Walkthrough

Test your integration without real money.

## 1. Get sandbox credentials

In your [Mercado Pago dashboard](https://www.mercadopago.com/developers):

1. Go to **Your integrations → your app → Test credentials**
2. Copy the `Access Token` (starts with `APP_USR-`)

## 2. Configure sandbox mode

1. Go to **Settings → Mercado Pago → Credentials**
2. Click **Production mode** in the header to switch to **Sandbox mode**
3. Enter the sandbox `Public Key` and `Access Token`
4. Click **Save changes**

## 3. Create test users

Mercado Pago requires a test seller and buyer:

```bash
# Using the Mercado Pago API (replace ACCESS_TOKEN)
curl -X POST \
  -H "Authorization: Bearer APP_USR-xxxxxxxx" \
  -H "Content-Type: application/json" \
  https://api.mercadopago.com/users/test_user \
  -d '{"site_id":"MLA","description":"Test seller"}'
```

Or create them from the [Test Accounts section](https://www.mercadopago.com/developers/panel/app) in your dashboard.

## 4. Test cards

Use these cards in sandbox:

| Card | Number | Result |
|---|---|---|
| Mastercard | `5031 7557 3453 0604` | Approved |
| Visa | `4509 9535 6623 3704` | Approved |
| Visa (debit) | `4002 7682 4114 2085` | Approved |
| Mastercard (rejected) | `5031 7557 3453 0604` | Rejected (any amount > 3000) |
| Any | `0000 0000 0000 0000` | Card not recognized |

- Expiration: any future date
- CVV: any 3 digits (4 for Amex)

## 5. Simulate a webhook

Use the built-in Artisan command to simulate a webhook notification:

```bash
# 1. Expose your local server
npx ngrok http 8000

# 2. Run the simulate command
php artisan mercadopago:webhook-simulate \
  --payment=123456789 \
  --url=https://your-tunnel.ngrok.io
```

This sends a fake `payment.created` notification to your local webhook endpoint with a valid `x-signature`.

## 6. Run sandbox integration tests

```bash
MERCADOPAGO_SANDBOX_ACCESS_TOKEN=TEST-xxx \
MERCADOPAGO_SANDBOX_PAYMENT_ID=123456789 \
./vendor/bin/pest --group=sandbox
```

These tests create real preferences and fetch real payments from the sandbox environment.
