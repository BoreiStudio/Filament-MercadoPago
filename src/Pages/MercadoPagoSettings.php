<?php

namespace BoreiStudio\FilamentMercadoPago\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class MercadoPagoSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Mercado Pago';
    protected static ?string $navigationLabel = 'Settings';
    protected static string $view = 'filament-mercadopago::pages.mercado-pago-settings';

    // Propiedades públicas para los campos que mostrarás
    public ?string $user_id_mp = null;
    public ?string $public_key = null;
    public ?string $access_token = null;
    public array $scope = [];
    public ?string $expires_in = null;

    public function mount(MercadoPagoService $mp): void
    {
        Log::debug('[MercadoPagoSettings] mount() called');

        $credentials = $mp->getCredentials() ?? [];

        Log::debug('[MercadoPagoSettings] credentials:', $credentials);

        // Setear los valores a las propiedades públicas para que Filament las use
        $this->fill([
            'user_id_mp' => $credentials['user_id_mp'] ?? null,
            'public_key' => $credentials['public_key'] ?? null,
            'access_token' => $credentials['access_token'] ?? null,
            'scope' => isset($credentials['scope']) ? explode(' ', $credentials['scope']) : [],
        ]);

        $expiresAt = $credentials['expires_in'] ?? null;

        if ($expiresAt) {
            $carbon = Carbon::parse($expiresAt);
            $this->expires_at = $carbon->translatedFormat('l j \d\e F, H:i'); // Ej: Domingo 7 de diciembre, 13:41
            $this->expires_in = $carbon->diffForHumans(null, true); // Ej: "5 months, 27 days"
        }

    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Credentials')
                ->schema([
                    Forms\Components\TextInput::make('user_id_mp')
                        ->label('MP User ID')
                        ->readOnly()
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('expires_in')
                        ->label('Expires in')
                        ->readOnly()
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('public_key')
                        ->label('Public Key')
                        ->readOnly()
                        ->suffixAction(
                            Action::make('copiar')
                                ->icon('heroicon-o-clipboard')
                                ->action(function (MercadoPagoSettings $livewire) {
                                    $livewire->js('navigator.clipboard.writeText("' . $livewire->public_key . '")');
                                })
                                ->tooltip('Copy to clipboard')
                        )
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('access_token')
                        ->label('Access Token')
                        ->password()
                        ->suffixAction(
                            Action::make('copiar')
                                ->icon('heroicon-o-clipboard')
                                ->tooltip('Copiar Access Token')
                                ->form([
                                    Forms\Components\TextInput::make('password')
                                        ->label('Contraseña')
                                        ->password()
                                        ->revealable()
                                        ->required(),
                                ])
                                ->action(function (array $data, MercadoPagoSettings $livewire) {
                                    $user = auth()->user();

                                    if (!\Hash::check($data['password'], $user->password)) {
                                        Notification::make()
                                            ->title('Contraseña incorrecta')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    $escaped = addslashes($livewire->access_token);
                                    $livewire->js("navigator.clipboard.writeText('{$escaped}')");

                                    Notification::make()
                                        ->title('Access Token copiado al portapapeles')
                                        ->success()
                                        ->send();
                                })
                                ->modalHeading('Verificar identidad')
                                ->modalSubmitActionLabel('Copiar')
                                ->modalCancelActionLabel('Cancelar')
                                ->modalWidth('sm')
                        )
                        ->readOnly()
                        ->columnSpan(2),

                    Forms\Components\TagsInput::make('scope')
                        ->label('Scopes')
                        ->placeholder('Available scopes')
                        ->disabled(),
                ])->columns(2)
        ];
    }
}
