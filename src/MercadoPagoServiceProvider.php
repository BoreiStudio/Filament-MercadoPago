<?php

namespace BoreiStudio\FilamentMercadoPago;

use BoreiStudio\FilamentMercadoPago\Console\Commands\WebhookSimulateCommand;
use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Models\QrOrder;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Models\Refund;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Models\WebhookEvent;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Policies\MercadoPagoAccountPolicy;
use BoreiStudio\FilamentMercadoPago\Policies\PaymentPolicy;
use BoreiStudio\FilamentMercadoPago\Policies\PointDevicePolicy;
use BoreiStudio\FilamentMercadoPago\Policies\PosTerminalPolicy;
use BoreiStudio\FilamentMercadoPago\Policies\QrOrderPolicy;
use BoreiStudio\FilamentMercadoPago\Policies\RefundPolicy;
use BoreiStudio\FilamentMercadoPago\Policies\StorePolicy;
use BoreiStudio\FilamentMercadoPago\Policies\WebhookEventPolicy;
use BoreiStudio\FilamentMercadoPago\Support\Credentials\MultiTenantCredentialResolver;
use BoreiStudio\FilamentMercadoPago\Support\Credentials\SingleTenantCredentialResolver;
use Filament\Facades\Filament;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MercadoPagoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-mercadopago')
            ->hasConfigFile('mercadopago')
            ->hasRoutes('web')
            ->hasTranslations()
            ->hasViews()
            ->hasMigrations([
                'create_mercadopago_accounts_table',
                'create_mercadopago_settings',
                'change_scope_to_text_in_mercadopago_accounts_table',
                'create_mercadopago_payments_table',
                'create_mercadopago_webhook_events_table',
                'create_mercadopago_refunds_table',
                'create_mercadopago_stores_table',
                'create_mercadopago_pos_table',
                'create_mercadopago_point_devices_table',
                'create_mercadopago_qr_orders_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(CredentialResolverInterface::class, function () {
            $mode = config('mercadopago.mode', 'single_tenant');

            if ($mode === 'multi_tenant') {
                return $this->app->make(MultiTenantCredentialResolver::class);
            }

            $panel = $this->resolvePanel();

            if ($panel && $panel->hasTenancy()) {
                return $this->app->make(MultiTenantCredentialResolver::class);
            }

            return $this->app->make(SingleTenantCredentialResolver::class);
        });
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'),
            Js::make('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'),
            Css::make('filament-mercadopago', __DIR__.'/../resources/dist/filament-mercadopago.css'),
        ], 'boreistudio/filament-mercadopago');

        if ($this->app->runningInConsole()) {
            $this->commands([
                WebhookSimulateCommand::class,
            ]);
        }

        Gate::policy(MercadoPagoAccount::class, MercadoPagoAccountPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Refund::class, RefundPolicy::class);
        Gate::policy(Store::class, StorePolicy::class);
        Gate::policy(PosTerminal::class, PosTerminalPolicy::class);
        Gate::policy(PointDevice::class, PointDevicePolicy::class);
        Gate::policy(QrOrder::class, QrOrderPolicy::class);
        Gate::policy(WebhookEvent::class, WebhookEventPolicy::class);
    }

    private function resolvePanel(): mixed
    {
        try {
            $panels = Filament::getPanels();

            return $panels[array_key_first($panels)] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
