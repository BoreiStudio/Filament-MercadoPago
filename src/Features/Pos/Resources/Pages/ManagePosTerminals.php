<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\PosResource;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class ManagePosTerminals extends ManageRecords
{
    protected static string $resource = PosResource::class;

    public static function getNavigationSort(): ?int
    {
        return 40;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('store.name')->label('Sucursal')->searchable(),
                TextColumn::make('external_id')->label('ID externo')->searchable(),
                TextColumn::make('mp_pos_id')->label('ID MP'),
                IconColumn::make('fixed_amount')->label('Monto fijo')->boolean(),
                TextColumn::make('category')->label('Categoría'),
                TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('ver_qr')
                    ->label('Ver QR')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn ($record) => $record->qr_image_url)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->qr_image_url)),
                EditAction::make()
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->form([
                        Section::make('Editar caja')->columns(2)->schema([
                            Select::make('store_id')->label('Sucursal')->options(Store::pluck('name', 'id'))->required()->searchable(),
                            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                            TextInput::make('external_id')->label('ID externo')->required()->maxLength(40)->rule('alpha_num'),
                            Select::make('category')->label('Categoría')->options([621102 => 'Gastronomía'])->helperText('Opcional.'),
                            Toggle::make('fixed_amount')->label('Monto fijo')->default(true),
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

                DeleteAction::make()
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

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(Width::TwoExtraLarge)
                ->label('Nueva caja')
                ->form([
                    Section::make('Nueva caja')->columns(2)->schema([
                        Select::make('store_id')->label('Sucursal')->options(Store::pluck('name', 'id'))->required()->searchable()->helperText('Creá una sucursal primero si no hay.'),
                        TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                        TextInput::make('external_id')->label('ID externo')->required()->maxLength(40)->rule('alpha_num'),
                        Select::make('category')->label('Categoría')->options([621102 => 'Gastronomía'])->helperText('Opcional.'),
                        Toggle::make('fixed_amount')->label('Monto fijo')->default(true),
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

                    $data['account_id'] = $account->id;
                    $data['mp_pos_id'] = $mpPos['id'];
                    $data['qr_image_url'] = $mpPos['qr']['image'] ?? $mpPos['qr_image'] ?? null;
                    $data['raw_payload'] = $mpPos;

                    return $data;
                }),

            Action::make('sync')
                ->label('Sincronizar desde MP')
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
                        PosTerminal::updateOrCreate(
                            ['mp_pos_id' => $p['id']],
                            ['account_id' => $account->id, 'name' => $p['name'] ?? 'Sin nombre', 'external_id' => $p['external_id'] ?? null, 'fixed_amount' => $p['fixed_amount'] ?? false, 'category' => $p['category'] ?? null, 'raw_payload' => $p]
                        );
                        $count++;
                    }
                    Notification::make()->title("{$count} cajas sincronizadas.")->success()->send();
                }),
        ];
    }
}
