<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Oauth\Pages;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoConfigCluster;
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\DisconnectAccountAction;
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\GenerateAuthorizationUrlAction;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ConnectMercadoPagoPage extends Page implements HasActions
{
    use InteractsWithActions;

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

    protected string $view = 'filament-mercadopago::oauth.connect';

    public ?MercadoPagoAccount $account = null;

    public function mount(): void
    {
        $this->account = MercadoPagoAccount::query()
            ->whereNull('tenant_id')
            ->whereNull('tenant_type')
            ->first();
    }

    protected function getHeaderActions(): array
    {
        if ($this->account && $this->account->isConnected()) {
            return [
                Action::make('refresh')
                    ->label(__('filament-mercadopago::messages.connection.reconnect'))
                    ->color('warning')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn () => $this->redirectToMercadoPago()),

                Action::make('disconnect')
                    ->label(__('filament-mercadopago::messages.connection.disconnect'))
                    ->color('danger')
                    ->icon('heroicon-o-link-slash')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament-mercadopago::messages.connection.disconnect_heading'))
                    ->modalDescription(__('filament-mercadopago::messages.connection.disconnect_body'))
                    ->modalSubmitActionLabel(__('filament-mercadopago::messages.connection.disconnect_submit'))
                    ->action(function (DisconnectAccountAction $action) {
                        $action->execute($this->account);
                        $this->account->refresh();

                        Notification::make()
                            ->title(__('filament-mercadopago::messages.connection.disconnected'))
                            ->success()
                            ->send();
                    }),
            ];
        }

        if ($this->account && $this->account->status === 'error') {
            return [
                Action::make('connect')
                    ->label(__('filament-mercadopago::messages.connection.reconnect_account'))
                    ->color('danger')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn () => $this->redirectToMercadoPago()),
            ];
        }

        return [
            Action::make('connect')
                ->label(__('filament-mercadopago::messages.connection.connect'))
                ->color('primary')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->action(fn () => $this->redirectToMercadoPago()),
        ];
    }

    private function redirectToMercadoPago(): void
    {
        $result = app(GenerateAuthorizationUrlAction::class)->execute();
        $this->redirect($result['url']);
    }

    public function connect(): void
    {
        $this->redirectToMercadoPago();
    }

    public function refresh(): void
    {
        $this->redirectToMercadoPago();
    }

    public function disconnect(DisconnectAccountAction $action): void
    {
        if (! $this->account) {
            return;
        }

        $action->execute($this->account);
        $this->account->refresh();

        Notification::make()
            ->title(__('filament-mercadopago::messages.connection.disconnected'))
            ->success()
            ->send();
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-mercadopago::messages.navigation.connect');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.navigation.connect');
    }

    public static function getNavigationSort(): ?int
    {
        return 11;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoConfigCluster::class;
    }
}
