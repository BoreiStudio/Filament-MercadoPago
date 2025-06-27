<?php

namespace BoreiStudio\FilamentMercadoPago;

use BoreiStudio\FilamentMercadoPago\Pages\MercadoPagoSettings;
use BoreiStudio\FilamentMercadoPago\Resources\PlanResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use BoreiStudio\FilamentMercadoPago\Resources\StoreResource;
use BoreiStudio\FilamentMercadoPago\Resources\TerminalResource;

class FilamentMercadoPagoPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'filament-mercado-pago';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            MercadoPagoSettings::class,
        ])
        ->resources([
            PlanResource::class,
            StoreResource::class,
            TerminalResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Si necesitás hacer algo cuando se carga el panel.
    }
}
