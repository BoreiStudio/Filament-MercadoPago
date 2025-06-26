<?php

namespace BoreiStudio\FilamentMercadoPago;

use Illuminate\Support\ServiceProvider;

class FilamentMercadoPagoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-mercadopago');

        $this->publishes([
            __DIR__.'/../config/filament-mercado-pago.php' => config_path('filament-mercado-pago.php'),
        ], 'filament-mercado-pago-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'filament-mercado-pago-migrations');
    }
}
