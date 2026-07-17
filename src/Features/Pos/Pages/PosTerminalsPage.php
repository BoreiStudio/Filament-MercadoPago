<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Pos\Pages;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoCluster;
use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PosTerminalsPage extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected string $view = 'filament-mercadopago::table-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(PosTerminal::query())
            ->columns([
                TextColumn::make('name')->label(__('filament-mercadopago::messages.pos.column_name'))->searchable()->sortable(),
                TextColumn::make('store.name')->label(__('filament-mercadopago::messages.pos.column_store'))->searchable(),
                TextColumn::make('external_id')->label(__('filament-mercadopago::messages.pos.column_external_id'))->searchable(),
                TextColumn::make('mp_pos_id')->label('ID MP'),
                IconColumn::make('fixed_amount')->label(__('filament-mercadopago::messages.pos.column_fixed_amount'))->boolean(),
                TextColumn::make('category')->label(__('filament-mercadopago::messages.pos.column_category')),
                TextColumn::make('created_at')->label(__('filament-mercadopago::messages.pos.column_created'))->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                CreateAction::make()
                    ->slideOver()->modalWidth(Width::TwoExtraLarge)
                    ->label(__('filament-mercadopago::messages.pos.new'))
                    ->form([
                        Section::make(__('filament-mercadopago::messages.pos.new'))->columns(2)->schema([
                            Select::make('store_id')->label(__('filament-mercadopago::messages.pos.column_store'))->options(Store::pluck('name', 'id'))->required()->searchable()->helperText(__('filament-mercadopago::messages.pos.store_hint')),
                            TextInput::make('name')->label(__('filament-mercadopago::messages.pos.column_name'))->required()->maxLength(255),
                            TextInput::make('external_id')->label(__('filament-mercadopago::messages.pos.column_external_id'))->required()->maxLength(40)->rule('alpha_num'),
                            Select::make('category')->label(__('filament-mercadopago::messages.pos.column_category'))->options([621102 => 'Gastronomía'])->helperText(__('filament-mercadopago::messages.pos.category_optional')),
                            Toggle::make('fixed_amount')->label(__('filament-mercadopago::messages.pos.column_fixed_amount'))->default(true),
                        ]),
                    ])
                    ->action(function (array $data) {
                        $credentials = app(CredentialResolverInterface::class)->resolve();
                        $account = MercadoPagoAccount::query()->where('mp_user_id', $credentials->getMpUserId())->first();
                        $store = Store::findOrFail($data['store_id']);

                        $payload = [
                            'name' => $data['name'],
                            'external_id' => $data['external_id'],
                            'store_id' => $store->mp_store_id,
                            'fixed_amount' => $data['fixed_amount'] ?? true,
                        ];

                        $externalStoreId = $store->raw_payload['external_id'] ?? $store->external_id;
                        if (filled($externalStoreId)) {
                            $payload['external_store_id'] = $externalStoreId;
                        }

                        if (filled($data['category'] ?? null)) {
                            $payload['category'] = (int) $data['category'];
                        }

                        $response = Http::withToken($credentials->getAccessToken())
                            ->post('https://api.mercadopago.com/pos', $payload)->throw();
                        $mpPos = $response->json();
                        PosTerminal::create([
                            'account_id' => $account->id, 'store_id' => $store->id, 'mp_pos_id' => $mpPos['id'],
                            'name' => $data['name'], 'external_id' => $data['external_id'],
                            'fixed_amount' => $data['fixed_amount'] ?? true, 'category' => $data['category'] ?? null,
                            'qr_image_url' => $mpPos['qr']['image'] ?? $mpPos['qr_image'] ?? null, 'raw_payload' => $mpPos,
                        ]);
                    }),

                Action::make('create_order')
                    ->label(__('filament-mercadopago::messages.pos.charge'))
                    ->icon('heroicon-o-credit-card')
                    ->slideOver()->modalWidth(Width::TwoExtraLarge)
                    ->form([
                        Section::make(__('filament-mercadopago::messages.pos.order_section'))->schema([
                            Grid::make(2)->schema([
                                Select::make('point_device_id')
                                    ->label(__('filament-mercadopago::messages.pos.terminal'))
                                    ->options(PointDevice::where('operating_mode', 'PDV')->pluck('device_id', 'id'))
                                    ->required()->searchable()
                                    ->helperText(__('filament-mercadopago::messages.pos.terminal_hint')),
                                TextInput::make('amount')
                                    ->label(__('filament-mercadopago::messages.pos.amount'))
                                    ->numeric()->required()->minValue(0.01)->prefix('$'),
                                TextInput::make('external_reference')
                                    ->label('Referencia externa')
                                    ->maxLength(64)->rule('regex:/^[a-zA-Z0-9\-_]+$/')
                                    ->helperText(__('filament-mercadopago::messages.pos.external_ref_hint')),
                                TextInput::make('description')
                                    ->label(__('filament-mercadopago::messages.pos.description'))
                                    ->maxLength(255),
                                Select::make('print_mode')
                                    ->label(__('filament-mercadopago::messages.pos.print_mode'))
                                    ->options([
                                        'no_ticket' => 'Sin ticket',
                                        'partial_ticket' => 'Ticket parcial',
                                        'full_ticket' => 'Ticket completo',
                                    ])->default('no_ticket'),
                                Select::make('payment_type')
                                    ->label('Tipo de pago')
                                    ->options([
                                        '' => 'Sin preferencia',
                                        'credit_card' => 'Tarjeta de crédito',
                                        'debit_card' => 'Tarjeta de débito',
                                    ])->default(''),
                            ]),
                        ]),
                    ])
                    ->action(function (array $data, CredentialResolverInterface $credentials) {
                        $device = PointDevice::findOrFail($data['point_device_id']);
                        $payload = [
                            'type' => 'point',
                            'external_reference' => $data['external_reference'] ?? uniqid('order-'),
                            'expiration_time' => 'PT15M',
                            'transactions' => [
                                'payments' => [
                                    ['amount' => number_format((float) $data['amount'], 2, '.', '')],
                                ],
                            ],
                            'config' => [
                                'point' => [
                                    'terminal_id' => $device->device_id,
                                    'print_on_terminal' => $data['print_mode'] ?? 'no_ticket',
                                ],
                            ],
                            'description' => $data['description'] ?? '',
                        ];
                        if (filled($data['payment_type'] ?? null)) {
                            $payload['config']['payment_method'] = [
                                'default_type' => $data['payment_type'],
                            ];
                        }
                        $response = Http::withToken($credentials->resolve()->getAccessToken())
                            ->withHeader('X-Idempotency-Key', (string) Str::uuid())
                            ->post('https://api.mercadopago.com/v1/orders', $payload)->throw();
                        $order = $response->json();
                        Notification::make()->title("Orden {$order['id']} creada. Enviada a la terminal.")->success()->send();
                    }),
                Action::make('sync_pos')
                    ->label('Sincronizar')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        $credentials = app(CredentialResolverInterface::class)->resolve();
                        $account = MercadoPagoAccount::query()->where('mp_user_id', $credentials->getMpUserId())->first();
                        if (! $account) {
                            Notification::make()->title('Sin cuenta conectada.')->danger()->send();

                            return;
                        }
                        $response = Http::withToken($credentials->getAccessToken())
                            ->get('https://api.mercadopago.com/pos')->throw();
                        $posList = $response->json('results', $response->json() ?? []);
                        $count = 0;
                        foreach ($posList as $p) {
                            PosTerminal::updateOrCreate(['mp_pos_id' => $p['id']],
                                ['account_id' => $account->id, 'name' => $p['name'] ?? 'Sin nombre', 'external_id' => $p['external_id'] ?? null, 'fixed_amount' => $p['fixed_amount'] ?? false, 'category' => $p['category'] ?? null, 'raw_payload' => $p]);
                            $count++;
                        }
                        Notification::make()->title("{$count} cajas sincronizadas.")->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('ver_qr')
                    ->label(__('filament-mercadopago::messages.pos.ver_qr'))
                    ->icon('heroicon-o-qr-code')
                    ->url(fn ($record) => $record->qr_image_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->qr_image_url)),
                EditAction::make('edit_pos')
                    ->slideOver()->modalWidth(Width::TwoExtraLarge)
                    ->form([
                        Section::make(__('filament-mercadopago::messages.pos.edit_section'))->columns(2)->schema([
                            Select::make('store_id')->label(__('filament-mercadopago::messages.pos.column_store'))->options(Store::pluck('name', 'id'))->required()->searchable(),
                            TextInput::make('name')->label(__('filament-mercadopago::messages.pos.column_name'))->required()->maxLength(255),
                            TextInput::make('external_id')->label(__('filament-mercadopago::messages.pos.column_external_id'))->required()->maxLength(40)->rule('alpha_num'),
                            Select::make('category')->label(__('filament-mercadopago::messages.pos.column_category'))->options([621102 => 'Gastronomía']),
                            Toggle::make('fixed_amount')->label(__('filament-mercadopago::messages.pos.column_fixed_amount'))->default(true),
                        ]),
                    ])
                    ->mutateRecordDataUsing(function (array $data, $record) {
                        $credentials = app(CredentialResolverInterface::class)->resolve();
                        $store = isset($data['store_id']) ? Store::find($data['store_id']) : null;

                        $payload = [
                            'name' => $data['name'],
                            'external_id' => $data['external_id'],
                            'fixed_amount' => $data['fixed_amount'] ?? true,
                            'store_id' => $store?->mp_store_id,
                        ];

                        if ($store) {
                            $externalStoreId = $store->raw_payload['external_id'] ?? $store->external_id;
                            if (filled($externalStoreId)) {
                                $payload['external_store_id'] = $externalStoreId;
                            }
                        }

                        if (filled($data['category'] ?? null)) {
                            $payload['category'] = (int) $data['category'];
                        }

                        if ($record->mp_pos_id) {
                            Http::withToken($credentials->getAccessToken())
                                ->put("https://api.mercadopago.com/pos/{$record->mp_pos_id}", $payload)->throw();
                        }

                        return $data;
                    }),
                DeleteAction::make('delete_pos')
                    ->before(function ($record) {
                        if ($record->mp_pos_id) {
                            try {
                                $credentials = app(CredentialResolverInterface::class)->resolve();
                                Http::withToken($credentials->getAccessToken())
                                    ->delete("https://api.mercadopago.com/pos/{$record->mp_pos_id}")->throw();
                            } catch (\Throwable $e) {
                                report($e);
                            }
                        }
                    }),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-mercadopago::messages.pos.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.pos.title');
    }

    public static function getNavigationSort(): ?int
    {
        return 40;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoCluster::class;
    }
}
