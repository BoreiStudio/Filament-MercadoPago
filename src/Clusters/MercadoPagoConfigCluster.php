<?php

namespace BoreiStudio\FilamentMercadoPago\Clusters;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;

class MercadoPagoConfigCluster extends Cluster
{
    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament-mercadopago::messages.navigation.settings_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.navigation.mp_group');
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationBadge(): ?string
    {
        $account = MercadoPagoAccount::query()
            ->whereNull('tenant_id')
            ->whereNull('tenant_type')
            ->first();

        if (! $account || $account->status === 'error' || ! $account->isConnected()) {
            return '!';
        }

        return '✓';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $account = MercadoPagoAccount::query()
            ->whereNull('tenant_id')
            ->whereNull('tenant_type')
            ->first();

        if (! $account || $account->status === 'error') {
            return 'danger';
        }

        if ($account->isConnected()) {
            return 'success';
        }

        return 'warning';
    }
}
