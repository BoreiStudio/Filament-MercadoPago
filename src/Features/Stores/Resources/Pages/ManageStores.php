<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\StoreResource;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class ManageStores extends ManageRecords
{
    protected static string $resource = StoreResource::class;

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('external_id')->label('ID externo')->searchable(),
                TextColumn::make('mp_store_id')->label('ID MP'),
                TextColumn::make('pos_terminals_count')->label('Cajas')->counts('posTerminals'),
                TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->form([
                        Section::make('Editar sucursal')->columns(2)->schema([
                            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                            TextInput::make('external_id')->label('ID externo')->maxLength(255),
                        ]),
                    ])
                    ->mutateRecordDataUsing(function (array $data, $record) {
                        $credentials = app(CredentialResolverInterface::class)->resolve();
                        Http::withToken($credentials->getAccessToken())
                            ->put("https://api.mercadopago.com/stores/{$record->mp_store_id}", [
                                'name' => $data['name'], 'external_id' => $data['external_id'] ?? null,
                            ])->throw();

                        return $data;
                    }),

                DeleteAction::make()
                    ->before(function ($record) {
                        if ($record->mp_store_id) {
                            try {
                                $credentials = app(CredentialResolverInterface::class)->resolve();
                                Http::withToken($credentials->getAccessToken())
                                    ->delete("https://api.mercadopago.com/stores/{$record->mp_store_id}")->throw();
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
                ->label('Nueva sucursal')
                ->form([
                    Section::make('Nueva sucursal')->columns(2)->schema([
                        TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                        TextInput::make('external_id')->label('ID externo')->helperText('Identificador único en tu sistema.')->maxLength(255),
                    ]),
                ])
                ->action(function (array $data) {
                    $credentials = app(CredentialResolverInterface::class)->resolve();
                    $account = MercadoPagoAccount::query()->where('mp_user_id', $credentials->getMpUserId())->first();

                    $response = Http::withToken($credentials->getAccessToken())
                        ->post('https://api.mercadopago.com/stores', [
                            'name' => $data['name'], 'external_id' => $data['external_id'] ?? null,
                        ])->throw();
                    $mpStore = $response->json();

                    $data['account_id'] = $account->id;
                    $data['mp_store_id'] = $mpStore['id'];
                    $data['raw_payload'] = $mpStore;

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
                        ->get('https://api.mercadopago.com/stores')->throw();
                    $stores = $response->json('stores', $response->json() ?? []);
                    $count = 0;

                    foreach ($stores as $s) {
                        Store::updateOrCreate(
                            ['mp_store_id' => $s['id']],
                            ['account_id' => $account->id, 'name' => $s['name'] ?? 'Sin nombre', 'external_id' => $s['external_id'] ?? null, 'raw_payload' => $s]
                        );
                        $count++;
                    }
                    Notification::make()->title("{$count} sucursales sincronizadas.")->success()->send();
                }),
        ];
    }
}
