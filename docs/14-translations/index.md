# Translations

## Overview

The plugin supports three languages out of the box:

- English (`en`) — default
- Spanish (`es`)
- Portuguese Brazil (`pt_BR`)

The active language is determined by `config('app.locale')` or Filament's `->locale()` setting.

## File structure

```
resources/lang/
  en/
    messages.php        → English (master)
  es/
    messages.php        → Spanish
  pt_BR/
    messages.php        → Portuguese (Brazil)
```

## Usage in code

```php
// In PHP
__('filament-mercadopago::messages.settings.title')
__('filament-mercadopago::messages.connection.connect')

// With parameters
__('filament-mercadopago::messages.settings.switched', ['mode' => 'Sandbox'])
```

## Adding a new language

1. Create a new directory: `resources/lang/{locale}/`
2. Copy `en/messages.php` as a template
3. Translate all values (keep keys unchanged)
4. Set the locale in your app or panel

## Publishing translations

To make translations editable outside the vendor:

```bash
php artisan vendor:publish --tag=filament-mercadopago-translations
```

Files will be published to `lang/vendor/filament-mercadopago/{locale}/messages.php`.

## Keys reference

| Prefix | Module |
|---|---|
| `settings.*` | Credentials page |
| `connection.*` | OAuth connection page |
| `oauth.*` | OAuth callback messages |
| `navigation.*` | Cluster and page navigation labels |
| `status.*` | Connection status labels |

## Troubleshooting

If translations don't take effect after changing the locale:

```bash
php artisan optimize:clear
```

Or ensure Filament's locale is set correctly in `AdminPanelProvider.php`:

```php
return $panel
    ->locale('es');  // Match your app locale
```
