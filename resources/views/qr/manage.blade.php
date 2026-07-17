<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Lista de cajas --}}
        <div class="lg:col-span-1">
            <x-filament::section :compact="true">
                <x-slot name="heading">Cajas con QR</x-slot>

                <div class="space-y-1 -mx-2">
                    @forelse ($posList as $pos)
                        @php $selected = $selectedPos?->id === $pos['id']; @endphp
                        <button
                            wire:click="selectPos({{ $pos['id'] }})"
                            class="w-full text-left px-3 py-2.5 rounded-lg text-sm transition flex items-center gap-3
                                {{ $selected
                                    ? 'bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5' }}"
                        >
                            <x-filament::icon
                                icon="heroicon-o-qr-code"
                                class="w-5 h-5 {{ $selected ? 'text-primary-600' : 'text-gray-400' }} shrink-0"
                            />
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $pos['name'] }}</p>
                                @php
                                    $hasOpen = collect($pos['qr_orders'] ?? [])->where('status', 'opened')->count();
                                @endphp
                                <p class="text-xs {{ $selected ? 'text-primary-400' : 'text-gray-500' }}">
                                    @if ($pos['qr_image_url'])
                                        QR estático
                                        @if ($hasOpen) · @endif
                                    @endif
                                    @if ($hasOpen)
                                        {{ $hasOpen }} orden(es)
                                    @endif
                                </p>
                            </div>
                        </button>
                    @empty
                        <p class="text-sm text-gray-500 px-3 py-4 text-center">No hay cajas con QR.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        {{-- Detalle --}}
        <div class="lg:col-span-2">
            @if ($selectedPos)
                <div class="space-y-6">
                    {{-- QR Estático --}}
                    @if ($selectedPos->qr_image_url)
                        <x-filament::section>
                            <x-slot name="heading">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-eye" class="w-5 h-5" />
                                    <span>QR Estático</span>
                                </div>
                            </x-slot>
                            <x-slot name="description">
                                Código permanente de {{ $selectedPos->name }}. El cliente ingresa el monto al escanear.
                            </x-slot>

                            <div class="flex justify-center py-2">
                                <img src="{{ $selectedPos->qr_image_url }}"
                                     alt="QR estático de {{ $selectedPos->name }}"
                                     class="w-48 h-48 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-white/10" />
                            </div>
                        </x-filament::section>
                    @endif

                    {{-- Orden dinámica activa --}}
                    @if ($currentOrder && $currentOrder->isOpened())
                        <x-filament::section>
                            <x-slot name="heading">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-shopping-cart" class="w-5 h-5" />
                                    <span>{{ $currentOrder->title }}</span>
                                </div>
                            </x-slot>

                            <div class="flex flex-col items-center py-6">
                                <p class="text-3xl font-bold tracking-tight">
                                    ${{ number_format($currentOrder->total_amount, 2, ',', '.') }}
                                </p>
                                <div class="flex items-center gap-2 mt-3">
                                    <x-filament::badge color="warning">Pendiente de pago</x-filament::badge>
                                    @if ($currentOrder->items)
                                        <span class="text-sm text-gray-500">{{ count($currentOrder->items) }} producto(s)</span>
                                    @endif
                                </div>

                                @if ($currentOrder->items)
                                    <div class="w-full max-w-sm mt-6 space-y-2">
                                        @foreach ($currentOrder->items as $item)
                                            <div class="flex justify-between text-sm py-1 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                                <span class="text-gray-600 dark:text-gray-400">
                                                    {{ $item['title'] ?? '' }}
                                                    <span class="text-gray-400">x{{ $item['quantity'] ?? 1 }}</span>
                                                </span>
                                                <span class="font-medium">
                                                    ${{ number_format(($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1), 2, ',', '.') }}
                                                </span>
                                            </div>
                                        @endforeach
                                        <div class="flex justify-between text-sm font-semibold pt-1">
                                            <span>Total</span>
                                            <span>${{ number_format($currentOrder->total_amount, 2, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </x-filament::section>
                    @endif
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center mb-5">
                        <x-filament::icon icon="heroicon-o-qr-code" class="w-10 h-10 text-gray-400" />
                    </div>
                    <p class="text-base font-medium text-gray-900 dark:text-white">Seleccioná una caja</p>
                    <p class="text-sm text-gray-500 mt-1 max-w-xs">
                        Elegí una caja de la lista para ver su código QR estático y generar órdenes dinámicas.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
