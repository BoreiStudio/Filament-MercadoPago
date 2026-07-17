<?php

namespace BoreiStudio\FilamentMercadoPago;

use Filament\Contracts\Plugin;
use Filament\Panel;

class MercadoPagoPlugin implements Plugin
{
    use Concerns\HasFeatureToggles;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-mercadopago';
    }

    public function register(Panel $panel): void
    {
        $this->registerFeatures($panel);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
