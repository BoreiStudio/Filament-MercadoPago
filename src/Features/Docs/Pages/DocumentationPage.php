<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Docs\Pages;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoConfigCluster;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

class DocumentationPage extends Page
{
    protected static ?string $slug = 'docs';

    protected string $view = 'filament-mercadopago::docs.page';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationSort(): ?int
    {
        return 11;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoConfigCluster::class;
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('viewAny', MercadoPagoAccount::class);
    }

    public string $selectedModule = '18-quick-start';

    public string $content = '';

    public array $modules = [];

    public function mount(): void
    {
        $this->modules = $this->getModules();
        $this->loadContent();
    }

    public function selectModule(string $module): void
    {
        $this->selectedModule = $module;
        $this->loadContent();
    }

    public function loadContent(): void
    {
        $modules = $this->getModules();
        $path = __DIR__.'/../../../../docs/'.$this->selectedModule.'/index.md';

        if (! isset($modules[$this->selectedModule]) || ! file_exists($path)) {
            $this->content = '<p class="text-gray-400">Document not found.</p>';

            return;
        }

        $this->content = Str::markdown(file_get_contents($path), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function copyMarkdown(): void
    {
        $path = __DIR__.'/../../../../docs/'.$this->selectedModule.'/index.md';

        if (! file_exists($path)) {
            return;
        }

        $this->js('navigator.clipboard.writeText('.json_encode(file_get_contents($path)).')');

        Notification::make()
            ->title('Markdown copied to clipboard.')
            ->success()
            ->send();
    }

    public function getModules(): array
    {
        return [
            '18-quick-start' => 'Quick Start',
            '01-installation' => 'Installation & Configuration',
            '02-oauth' => 'OAuth — Connect Account',
            '03-credentials' => 'Application Credentials',
            '04-checkout-pro' => 'Checkout Pro',
            '05-webhooks' => 'Webhooks',
            '06-refunds' => 'Refunds',
            '07-stores' => 'Stores',
            '08-pos' => 'POS Terminals',
            '09-point' => 'Point Devices',
            '10-qr' => 'QR Codes',
            '11-dashboard' => 'Dashboard',
            '12-multi-tenant' => 'Multi-Tenant',
            '13-feature-toggles' => 'Feature Toggles',
            '14-translations' => 'Translations',
            '15-security' => 'Security',
            '16-testing' => 'Testing',
            '17-api-reference' => 'API Reference',
            '19-sandbox-walkthrough' => 'Sandbox Walkthrough',
            '20-troubleshooting' => 'Troubleshooting & FAQ',
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Documentation';
    }

    public static function getNavigationLabel(): string
    {
        return 'Documentation';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-book-open';
    }
}
