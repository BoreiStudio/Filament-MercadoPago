<?php

namespace BoreiStudio\FilamentMercadoPago\Forms\Components;

use Filament\Forms\Components\Field;

class MapPicker extends Field
{
    protected string $view = 'filament-mercadopago::forms.map-picker';

    public function defaultLat(float $lat): static
    {
        $this->default($lat);

        return $this;
    }

    public function defaultLng(float $lng): static
    {
        return $this;
    }
}
