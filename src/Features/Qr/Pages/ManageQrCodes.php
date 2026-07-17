<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Qr\Pages;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoCluster;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Actions\CreateQrOrderAction;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Actions\DeleteQrOrderAction;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Models\QrOrder;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ManageQrCodes extends Page implements HasActions
{
    use InteractsWithActions;

    protected string $view = 'filament-mercadopago::qr.manage';

    public ?PosTerminal $selectedPos = null;

    public array $posList = [];

    public ?QrOrder $currentOrder = null;

    public function mount(): void
    {
        $this->posList = PosTerminal::query()
            ->whereNotNull('qr_image_url')
            ->orWhereHas('qrOrders', fn ($q) => $q->where('status', 'opened'))
            ->with('qrOrders')
            ->get()
            ->toArray();
    }

    public function selectPos(int $posId): void
    {
        $this->selectedPos = PosTerminal::with('qrOrders')->find($posId);
        /** @var QrOrder|null $order */
        $order = $this->selectedPos?->qrOrders()
            ->where('status', 'opened')
            ->latest()
            ->first();
        $this->currentOrder = $order;
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-mercadopago::messages.qr.title');
    }

    public static function getNavigationLabel(): string
    {
        return 'QR';
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoCluster::class;
    }

    protected function getHeaderActions(): array
    {
        if (! $this->selectedPos) {
            return [];
        }

        return [
            Action::make('createQr')
                ->label(__('filament-mercadopago::messages.qr.generate'))
                ->icon('heroicon-o-qr-code')
                ->color('primary')
                ->form([
                    TextInput::make('title')
                        ->label(__('filament-mercadopago::messages.qr.title_field'))
                        ->required()
                        ->default('Pedido '.$this->selectedPos->name),

                    Repeater::make('items')
                        ->label(__('filament-mercadopago::messages.qr.products'))
                        ->schema([
                            TextInput::make('title')
                                ->label(__('filament-mercadopago::messages.qr.product'))
                                ->required()
                                ->maxLength(255),

                            TextInput::make('quantity')
                                ->label(__('filament-mercadopago::messages.qr.quantity'))
                                ->numeric()
                                ->default(1)
                                ->required(),

                            TextInput::make('unit_price')
                                ->label(__('filament-mercadopago::messages.qr.unit_price'))
                                ->numeric()
                                ->required()
                                ->prefix('$'),
                        ])
                        ->defaultItems(1)
                        ->required(),

                    TextInput::make('external_reference')
                        ->label(__('filament-mercadopago::messages.qr.external_reference'))
                        ->maxLength(255),
                ])
                ->action(function (array $data, CreateQrOrderAction $action) {
                    $items = array_map(fn ($item) => [
                        'title' => $item['title'],
                        'quantity' => (int) $item['quantity'],
                        'unit_price' => (float) $item['unit_price'],
                    ], $data['items']);

                    $order = $action->execute(
                        pos: $this->selectedPos,
                        items: $items,
                        externalReference: $data['external_reference'] ?? uniqid('qr-'),
                        title: $data['title'],
                    );

                    $this->currentOrder = $order;

                    Notification::make()
                        ->title(__('filament-mercadopago::messages.qr.generated'))
                        ->success()
                        ->send();
                }),

            Action::make('closeOrder')
                ->label(__('filament-mercadopago::messages.qr.close'))
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn () => $this->currentOrder && $this->currentOrder->isOpened())
                ->requiresConfirmation()
                ->action(function (DeleteQrOrderAction $action) {
                    $action->execute($this->currentOrder);
                    $this->currentOrder = null;

                    Notification::make()
                        ->title(__('filament-mercadopago::messages.qr.closed'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
