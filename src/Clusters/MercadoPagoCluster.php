<?php

namespace BoreiStudio\FilamentMercadoPago\Clusters;

use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class MercadoPagoCluster extends Cluster
{
    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.navigation.mp_group');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-currency-dollar';
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }
}
