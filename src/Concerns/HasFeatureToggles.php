<?php

namespace BoreiStudio\FilamentMercadoPago\Concerns;

use BoreiStudio\FilamentMercadoPago\Features\Dashboard\Widgets\MercadoPagoStatsWidget;
use BoreiStudio\FilamentMercadoPago\Features\Docs\Pages\DocumentationPage;
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Pages\ConnectMercadoPagoPage;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Pages\PaymentsPage;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Resources\PaymentResource;
use BoreiStudio\FilamentMercadoPago\Features\Point\Pages\PointDevicesPage;
use BoreiStudio\FilamentMercadoPago\Features\Point\Resources\PointDeviceResource;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Pages\PosTerminalsPage;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\PosResource;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Pages\ManageQrCodes;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Pages\StoresPage;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\StoreResource;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Pages\WebhookEventsPage;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources\WebhookEventResource;
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoSettingsPage;
use Filament\Panel;

trait HasFeatureToggles
{
    protected bool $hasPayments = true;

    protected bool $hasRefunds = true;

    protected bool $hasPoint = false;

    protected bool $hasQr = false;

    protected bool $hasStores = false;

    protected bool $hasDocumentation = true;

    protected bool $hasDashboard = true;

    protected ?string $navigationGroup = null;

    public function payments(bool $condition = true): static
    {
        $this->hasPayments = $condition;

        return $this;
    }

    public function isPaymentsEnabled(): bool
    {
        return $this->hasPayments;
    }

    public function refunds(bool $condition = true): static
    {
        $this->hasRefunds = $condition;

        return $this;
    }

    public function isRefundsEnabled(): bool
    {
        return $this->hasRefunds;
    }

    public function point(bool $condition = true): static
    {
        $this->hasPoint = $condition;

        if ($condition) {
            $this->hasStores = true;
        }

        return $this;
    }

    public function isPointEnabled(): bool
    {
        return $this->hasPoint;
    }

    public function qr(bool $condition = true): static
    {
        $this->hasQr = $condition;

        if ($condition) {
            $this->hasStores = true;
        }

        return $this;
    }

    public function isQrEnabled(): bool
    {
        return $this->hasQr;
    }

    public function stores(bool $condition = true): static
    {
        $this->hasStores = $condition;

        return $this;
    }

    public function isStoresEnabled(): bool
    {
        return $this->hasStores;
    }

    public function documentation(bool $condition = true): static
    {
        $this->hasDocumentation = $condition;

        return $this;
    }

    public function isDocumentationEnabled(): bool
    {
        return $this->hasDocumentation;
    }

    public function dashboard(bool $condition = true): static
    {
        $this->hasDashboard = $condition;

        return $this;
    }

    public function isDashboardEnabled(): bool
    {
        return $this->hasDashboard;
    }

    public function navigationGroup(?string $group = null): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    protected function registerFeatures(Panel $panel): void
    {
        $resources = [];
        $pages = [];

        if ($this->hasPayments) {
            $resources[] = PaymentResource::class;
            $resources[] = WebhookEventResource::class;
            $pages[] = PaymentsPage::class;
        }

        if ($this->hasStores) {
            $resources[] = StoreResource::class;
            $resources[] = PosResource::class;
            $pages[] = StoresPage::class;
            $pages[] = PosTerminalsPage::class;
        }

        if ($this->hasPoint) {
            $resources[] = PointDeviceResource::class;
            $pages[] = PointDevicesPage::class;
        }

        if ($this->hasQr) {
            $pages[] = ManageQrCodes::class;
        }

        if ($this->hasPayments) {
            $pages[] = WebhookEventsPage::class;
        }

        $defaultPages = [
            MercadoPagoSettingsPage::class,
            ConnectMercadoPagoPage::class,
        ];

        if ($this->hasDocumentation) {
            $defaultPages[] = DocumentationPage::class;
        }

        $widgets = [];

        if ($this->hasDashboard) {
            $widgets[] = MercadoPagoStatsWidget::class;
        }

        $panel
            ->discoverClusters(
                in: __DIR__.'/../Clusters',
                for: 'BoreiStudio\\FilamentMercadoPago\\Clusters',
            )
            ->widgets($widgets)
            ->resources($resources)
            ->pages(array_merge($defaultPages, $pages));
    }
}
