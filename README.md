# Filament Mercado Pago Package

This package provides a seamless integration of Mercado Pago payments within your Filament PHP application, allowing you to connect Mercado Pago accounts and manage their credentials securely.

## 1. Introduction

The `FilamentMercadoPago` package simplifies the process of integrating Mercado Pago into your Filament projects. It handles the OAuth connection flow, securely stores Mercado Pago credentials (Access Token, Public Key, Refresh Token), and provides a convenient service to access these credentials throughout your application.

## 2. Installation

To get started with the Filament Mercado Pago package, follow these steps:

### 2.1 Install the Package

Install the package via Composer:

```bash
composer require boreistudio/filament-mercado-pago
```

### 2.2 Publish Assets (Configuration and Migrations)

After installing the package, you need to publish its assets. You have two options:

#### Option 1: Use the Installation Command (Recommended)

This command will publish both the configuration file and the necessary database migrations:

```bash
php artisan filament-mercado-pago:install
```

#### Option 2: Publish Manually

Alternatively, you can publish the assets individually:

To publish the migrations:

```bash
php artisan vendor:publish --tag="filament-mercado-pago-migrations"
```

To publish the configuration file (optional, if you need to customize settings):

```bash
php artisan vendor:publish --tag="filament-mercado-pago-config"
```

After publishing the assets (using either option), remember to execute your database migrations:

```bash
php artisan migrate
```

**Note on Column Length:** If you encounter a `SQLSTATE[22001]: String data, right truncated` error after migration, it means the encrypted tokens are too long for the default column sizes. You might need to manually adjust the `access_token` and `public_key` columns to `TEXT` type in your migration or modify the existing `mercado_pago_accounts` table migration.

## 3. Configuration

After publishing the configuration, you'll find `config/filament-mercado-pago.php`. Update the following values with your Mercado Pago application credentials:

```php
// config/filament-mercado-pago.php
return [
    'client_id' => env('MERCADO_PAGO_CLIENT_ID'),
    'client_secret' => env('MERCADO_PAGO_CLIENT_SECRET'),
    'redirect_uri' => env('MERCADO_PAGO_REDIRECT_URI', 'http://localhost/mercadopago/callback'),
];

```
Ensure you have these environment variables set in your `.env` file:

```dotenv
MERCADO_PAGO_CLIENT_ID=your_client_id
MERCADO_PAGO_CLIENT_SECRET=your_client_secret
MERCADO_PAGO_REDIRECT_URI=https://your-domain.com/mercadopago/callback
```
The `redirect_uri` should match the one configured in your Mercado Pago application.

## 4. Usage

### 4.1 Connecting Mercado Pago Account

The package provides a `ConnectController` to handle the OAuth redirection and callback from Mercado Pago.

**Redirection to Mercado Pago:**
```php
// packages/BoreiStudio/FilamentMercadoPago/src/Http/Controllers/ConnectController.php
public function redirect()
{
    // ...
}
```
This method constructs the authorization URL and redirects the user to Mercado Pago for authentication.

**Handling the Callback:**
```php
// packages/BoreiStudio/FilamentMercadoPago/src/Http/Controllers/ConnectController.php
public function callback(Request $request)
{
    // ...
}
```
This method processes the callback from Mercado Pago, exchanges the authorization code for access and refresh tokens, encrypts them, and saves them to the `mercado_pago_accounts` table.

### 4.2 Accessing Credentials Globally

The package provides a `MercadoPagoService` to conveniently access and decrypt the stored Mercado Pago credentials.

**Registering the Service:**
The `FilamentMercadoPagoServiceProvider` automatically registers the `MercadoPagoService` as a singleton in Laravel's service container.

```php
// packages/BoreiStudio/FilamentMercadoPago/src/FilamentMercadoPagoServiceProvider.php
namespace BoreiStudio\FilamentMercadoPago;

use Illuminate\Support\ServiceProvider;
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoService;

class FilamentMercadoPagoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(MercadoPagoService::class, function ($app) {
            return new MercadoPagoService();
        });
    }

    public function boot()
    {
        //
    }
}
```

**Using the Service:**

You can inject the `MercadoPagoService` into your controllers, services, or any other class:

```php
// Example: In a controller
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoService;

class YourController extends Controller
{
    protected $mercadoPagoService;

    public function __construct(MercadoPagoService $mercadoPagoService)
    {
        $this->mercadoPagoService = $mercadoPagoService;
    }

    public function someMethod()
    {
        $accessToken = $this->mercadoPagoService->getAccessToken();
        $publicKey = $this->mercadoPagoService->getPublicKey();
        $refreshToken = $this->mercadoPagoService->getRefreshToken();
        $allCredentials = $this->mercadoPagoService->getCredentials();

        // Use the credentials here (e.g., make API calls to Mercado Pago)
        // ...
    }
}
```

Alternatively, you can resolve it from the container using the `app()` helper:

```php
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoService;

$mercadoPagoService = app(MercadoPagoService::class);
$accessToken = $mercadoPagoService->getAccessToken();
```

The methods `getAccessToken()`, `getPublicKey()`, `getRefreshToken()`, and `getCredentials()` will automatically decrypt the stored values. You can also pass a `user_id` to these methods if you need to retrieve credentials for a specific user other than the currently authenticated one.

## 5. Security Notes

*   **Encryption:** `access_token` and `public_key` are encrypted before being stored in the database.
*   **Access Token:** The `access_token` is a sensitive private key and should only be used on your backend. Never expose it in your frontend.
*   **Public Key:** While `public_key` is designed for frontend use (e.g., for card data encryption), it is still stored encrypted for consistency and added security.
*   **PKCE:** The integration uses PKCE (Proof Key for Code Exchange) for enhanced security during the OAuth flow.

--- 