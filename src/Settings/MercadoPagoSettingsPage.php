<?php

namespace BoreiStudio\FilamentMercadoPago\Settings;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoConfigCluster;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class MercadoPagoSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithForms;

    protected Width|string|null $maxContentWidth = Width::ScreenTwoExtraLarge;

    protected string $view = 'filament-mercadopago::settings-page';

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

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(MercadoPagoApplicationSettings::class);

        $this->form->fill([
            'client_id' => $settings->client_id ?? '',
            'client_secret' => $settings->client_secret ?? '',
            'public_key' => $settings->public_key ?? '',
            'access_token' => $settings->access_token ?? '',
            'country' => $settings->country ?? 'MLA',
            'redirect_uri' => config('app.url').'/mercadopago/oauth/callback',
            'webhook_secret' => $settings->webhook_secret ?? '',
            'sandbox_mode' => $settings->sandbox_mode ?? false,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $countryOptions = fn () => [
            'MLA' => __('filament-mercadopago::messages.settings.country_MLA'),
            'MLB' => __('filament-mercadopago::messages.settings.country_MLB'),
            'MLC' => __('filament-mercadopago::messages.settings.country_MLC'),
            'MCO' => __('filament-mercadopago::messages.settings.country_MCO'),
            'MLM' => __('filament-mercadopago::messages.settings.country_MLM'),
            'MPE' => __('filament-mercadopago::messages.settings.country_MPE'),
            'MLU' => __('filament-mercadopago::messages.settings.country_MLU'),
        ];

        return $form
            ->statePath('data')
            ->schema([
                Section::make(__('filament-mercadopago::messages.settings.title'))
                    ->description(__('filament-mercadopago::messages.settings.description'))
                    ->headerActions([
                        Action::make('toggle_sandbox')
                            ->label(fn () => $this->getModeLabel())
                            ->color(fn () => ($this->data['sandbox_mode'] ?? false) ? 'warning' : 'gray')
                            ->icon(fn () => ($this->data['sandbox_mode'] ?? false) ? 'heroicon-o-beaker' : 'heroicon-o-rocket-launch')
                            ->requiresConfirmation()
                            ->modalHeading(fn () => $this->getSwitchConfirmTitle())
                            ->modalDescription(fn () => $this->getSwitchConfirmBody())
                            ->modalSubmitActionLabel(__('filament-mercadopago::messages.settings.switch_submit'))
                            ->action(function () {
                                $this->data['sandbox_mode'] = ! ($this->data['sandbox_mode'] ?? false);
                                $this->persistSettings();
                                Notification::make()
                                    ->title($this->getSwitchedNotification())
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->footerActions([
                        Action::make('save')
                            ->label(__('filament-mercadopago::messages.settings.save'))
                            ->action('save')
                            ->color('primary'),
                    ])
                    ->schema([
                        Tabs::make()->tabs([
                            Tab::make(__('filament-mercadopago::messages.settings.production_tab'))
                                ->schema([
                                    Section::make()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('client_id')
                                                ->label(__('filament-mercadopago::messages.settings.client_id'))
                                                ->maxLength(255),

                                            TextInput::make('client_secret')
                                                ->label(__('filament-mercadopago::messages.settings.client_secret'))
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255),

                                            TextInput::make('redirect_uri')
                                                ->label(__('filament-mercadopago::messages.settings.redirect_uri'))
                                                ->helperText(__('filament-mercadopago::messages.settings.redirect_uri_hint'))
                                                ->disabled()
                                                ->copyable()
                                                ->maxLength(255),

                                            TextInput::make('webhook_secret')
                                                ->label(__('filament-mercadopago::messages.settings.webhook_secret'))
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255),

                                            TextInput::make('public_key')
                                                ->label(__('filament-mercadopago::messages.settings.public_key'))
                                                ->maxLength(255),

                                            TextInput::make('access_token')
                                                ->label(__('filament-mercadopago::messages.settings.access_token'))
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255),

                                            Select::make('country')
                                                ->label(__('filament-mercadopago::messages.settings.country'))
                                                ->options($countryOptions)
                                                ->helperText(__('filament-mercadopago::messages.settings.country_hint'))
                                                ->live(),
                                        ]),
                                ]),

                            Tab::make(__('filament-mercadopago::messages.settings.sandbox_tab'))
                                ->schema([
                                    Section::make()
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('public_key')
                                                ->label(__('filament-mercadopago::messages.settings.public_key'))
                                                ->maxLength(255),

                                            TextInput::make('access_token')
                                                ->label(__('filament-mercadopago::messages.settings.access_token'))
                                                ->password()
                                                ->revealable()
                                                ->maxLength(255),

                                            Select::make('country')
                                                ->label(__('filament-mercadopago::messages.settings.country'))
                                                ->options($countryOptions)
                                                ->helperText(__('filament-mercadopago::messages.settings.country_hint'))
                                                ->live(),
                                        ]),
                                ]),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $this->persistSettings();

        Notification::make()
            ->title(__('filament-mercadopago::messages.settings.saved'))
            ->success()
            ->send();
    }

    private function persistSettings(): void
    {
        $state = $this->form->getState();

        $settings = app(MercadoPagoApplicationSettings::class);
        $settings->client_id = $state['client_id'] ?? '';
        $settings->client_secret = $state['client_secret'] ?? '';
        $settings->public_key = $state['public_key'] ?? '';
        $settings->access_token = $state['access_token'] ?? '';
        $settings->redirect_uri = config('app.url').'/mercadopago/oauth/callback';
        $settings->webhook_secret = $state['webhook_secret'] ?? '';
        $settings->country = $state['country'] ?? 'MLA';
        $settings->sandbox_mode = $this->data['sandbox_mode'] ?? false;
        $settings->save();
    }

    private function getModeLabel(): string
    {
        $key = ($this->data['sandbox_mode'] ?? false)
            ? 'filament-mercadopago::messages.settings.sandbox_mode'
            : 'filament-mercadopago::messages.settings.production_mode';

        return __($key);
    }

    private function getSwitchConfirmTitle(): string
    {
        $mode = ($this->data['sandbox_mode'] ?? false)
            ? __('filament-mercadopago::messages.settings.production_tab')
            : __('filament-mercadopago::messages.settings.sandbox_tab');

        return __('filament-mercadopago::messages.settings.switch_confirm_title', ['mode' => $mode]);
    }

    private function getSwitchConfirmBody(): string
    {
        $mode = ($this->data['sandbox_mode'] ?? false)
            ? __('filament-mercadopago::messages.settings.production_tab')
            : __('filament-mercadopago::messages.settings.sandbox_tab');

        return __('filament-mercadopago::messages.settings.switch_confirm_body', ['mode' => $mode]);
    }

    private function getSwitchedNotification(): string
    {
        $mode = $this->data['sandbox_mode']
            ? __('filament-mercadopago::messages.settings.sandbox_tab')
            : __('filament-mercadopago::messages.settings.production_tab');

        return __('filament-mercadopago::messages.settings.switched', ['mode' => $mode]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-mercadopago::messages.navigation.credentials');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.navigation.credentials');
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoConfigCluster::class;
    }
}
