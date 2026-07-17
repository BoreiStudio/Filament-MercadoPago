# Filament MercadoPago Plugin

Filament v5 plugin that integrates Mercado Pago payments into your Laravel application. Supports Checkout Pro (online payments), Point (in-person terminals), QR codes, OAuth account connection, webhooks, refunds, and in-panel documentation.

## Requirements

- PHP 8.3+
- Laravel 11+ / 12+
- Filament 5.x
- [Mercado Pago developer account](https://www.mercadopago.com/developers)

The full documentation is also accessible from the Filament panel at **Settings → Mercado Pago → Documentation**.

## Documentation

| # | Module | Description |
|---|---|---|
| 01 | [Installation & Configuration](01-installation/) | Install, configure, single vs multi-tenant |
| 02 | [OAuth — Connect Account](02-oauth/) | OAuth flow, refresh tokens, PKCE |
| 03 | [Application Credentials](03-credentials/) | Settings page, client_id, client_secret, webhook_secret |
| 04 | [Checkout Pro](04-checkout-pro/) | CreatePreferenceAction, items, init_point |
| 05 | [Webhooks](05-webhooks/) | Signature validation, event log, reprocess |
| 06 | [Refunds](06-refunds/) | CreateRefundAction, total/partial, validations |
| 07 | [Stores](07-stores/) | CRUD stores, sync, location, map picker |
| 08 | [POS Terminals](08-pos/) | CRUD POS, sync, QR image, categories |
| 09 | [Point Devices](09-point/) | Devices, create orders, operating modes |
| 10 | [QR Codes](10-qr/) | Static & dynamic QR, ManageQrCodes |
| 11 | [Dashboard](11-dashboard/) | Stats widget, cluster navigation, badges |
| 12 | [Multi-Tenant](12-multi-tenant/) | Tenant isolation, credential resolver |
| 13 | [Feature Toggles](13-feature-toggles/) | Plugin::make()->payments()->point()->... |
| 14 | [Translations](14-translations/) | Add/modify languages (en/es/pt_BR) |
| 15 | [Security](15-security/) | Encrypted tokens, webhook HMAC, server-side validation |
| 16 | [Testing](16-testing/) | Unit tests, sandbox integration tests |
| 17 | [API Reference](17-api-reference/) | All public Actions, Models, Policies |
| 18 | [Quick Start](18-quick-start/) | Get from zero to a working payment in 5 minutes |
| 19 | [Sandbox Walkthrough](19-sandbox-walkthrough/) | Test credentials, cards, simulate webhooks |
| 20 | [Troubleshooting & FAQ](20-troubleshooting/) | Common errors, solutions, frequently asked questions |
