<x-filament-panels::page>
    @if ($account && $account->isConnected())
        <x-filament::section :collapsible="true">
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-base">
                    <x-filament::icon icon="heroicon-o-check-circle" class="w-5 h-5 text-success-600" />
                    <span>Cuenta conectada</span>
                </div>
            </x-slot>

            @php
                $info = [
                    ['label' => 'User ID', 'value' => $account->mp_user_id, 'mono' => true],
                    ['label' => 'Entorno', 'value' => [
                        'text' => $account->live_mode ? 'Producción' : 'Sandbox',
                        'badge' => $account->live_mode ? 'warning' : 'info',
                    ]],
                    ['label' => 'Public Key', 'value' => $account->public_key, 'mono' => true, 'break' => true],
                    ['label' => 'Token expira', 'value' => $account->expires_at?->format('d/m/Y H:i') ?? '—'],
                    ['label' => 'Conectado desde', 'value' => $account->created_at?->diffForHumans() ?? '—'],
                    ['label' => 'Estado', 'value' => [
                        'text' => $account->isExpired() ? 'Expirado' : 'Vigente',
                        'badge' => $account->isExpired() ? 'danger' : 'success',
                    ]],
                ];
            @endphp

            <dl class="space-y-3">
                @foreach ($info as $item)
                    <div class="sm:flex sm:items-baseline sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500 sm:w-36 shrink-0">{{ $item['label'] }}</dt>
                        <dd class="mt-0.5 sm:mt-0 text-sm">
                            @if (isset($item['value']['badge']))
                                <x-filament::badge :color="$item['value']['badge']">
                                    {{ $item['value']['text'] }}
                                </x-filament::badge>
                            @elseif ($item['mono'] ?? false)
                                <span class="font-mono {{ ($item['break'] ?? false) ? 'break-all text-xs' : '' }}">
                                    {{ $item['value'] }}
                                </span>
                            @else
                                <span class="font-medium">{{ $item['value'] }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>

    @elseif ($account && $account->status === 'error')
        <div class="flex flex-col items-center py-20">
            <x-filament::icon
                icon="heroicon-o-exclamation-circle"
                class="w-16 h-16 text-danger-600 mb-6"
            />
            <p class="text-lg font-medium">Error de conexión</p>
            <p class="text-sm text-gray-500 mt-1 mb-8 max-w-md text-center">
                El token de acceso expiró o fue revocado por Mercado Pago. Hacé clic en Reconectar para volver a vincular tu cuenta.
            </p>
        </div>

    @else
        <div class="flex flex-col items-center py-20">
            <x-filament::icon
                icon="heroicon-o-link"
                class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-6"
            />
            <p class="text-lg font-medium">Sin conexión</p>
            <p class="text-sm text-gray-500 mt-1 mb-8 max-w-md text-center">
                Conectá tu cuenta de Mercado Pago para habilitar los cobros desde este panel.
            </p>
        </div>
    @endif
</x-filament-panels::page>
