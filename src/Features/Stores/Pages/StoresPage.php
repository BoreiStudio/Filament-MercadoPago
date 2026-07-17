<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Stores\Pages;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoCluster;
use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
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
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Http;

class StoresPage extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected string $view = 'filament-mercadopago::table-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(Store::query())
            ->columns([
                TextColumn::make('name')->label(__('filament-mercadopago::messages.store.column_name'))->searchable()->sortable(),
                TextColumn::make('external_id')->label(__('filament-mercadopago::messages.store.column_external_id'))->searchable(),
                TextColumn::make('mp_store_id')->label('ID MP'),
                TextColumn::make('pos_terminals_count')->label(__('filament-mercadopago::messages.store.column_pos_count'))->counts('posTerminals'),
                TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                CreateAction::make()
                    ->slideOver()->modalWidth(Width::TwoExtraLarge)
                    ->label(__('filament-mercadopago::messages.store.new'))
                    ->form([
                        Section::make(__('filament-mercadopago::messages.store.basic_info_section'))->columns(2)->schema([
                            TextInput::make('name')->label(__('filament-mercadopago::messages.store.column_name'))->required()->maxLength(255),
                            TextInput::make('external_id')->label(__('filament-mercadopago::messages.store.column_external_id'))->helperText(__('filament-mercadopago::messages.store.external_id_hint'))->maxLength(255),
                        ]),
                        Section::make(__('filament-mercadopago::messages.store.location_section'))->columns(2)->schema([
                            TextInput::make('street_name')->label(__('filament-mercadopago::messages.store.street'))->maxLength(255),
                            TextInput::make('street_number')->label(__('filament-mercadopago::messages.store.street_number'))->maxLength(20),
                            Select::make('state_name')
                                ->label(__('filament-mercadopago::messages.store.province'))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('city_name', null))
                                ->options(fn () => collect(
                                    Http::get('https://api.mercadolibre.com/classified_locations/countries/AR')->json('states')
                                )->pluck('name', 'id')),
                            Select::make('city_name')
                                ->label(__('filament-mercadopago::messages.store.city'))
                                ->searchable()
                                ->disabled(fn (callable $get) => ! $get('state_name'))
                                ->options(function (callable $get) {
                                    $stateId = $get('state_name');
                                    if (! $stateId) {
                                        return [];
                                    }

                                    return collect(
                                        Http::get("https://api.mercadolibre.com/classified_locations/states/{$stateId}")->json('cities')
                                    )->pluck('name', 'name');
                                }),
                            TextInput::make('latitude')->label(__('filament-mercadopago::messages.store.latitude'))->numeric()->step(0.000001)->reactive(),
                            TextInput::make('longitude')->label(__('filament-mercadopago::messages.store.longitude'))->numeric()->step(0.000001)->reactive(),
                        ]),
                        ViewField::make('map_picker_create')->view('filament-mercadopago::forms.map-picker'),
                    ])
                    ->action(function (array $data) {
                        $credentials = app(CredentialResolverInterface::class)->resolve();
                        $account = MercadoPagoAccount::query()->where('mp_user_id', $credentials->getMpUserId())->first();

                        $payload = [
                            'name' => $data['name'],
                            'external_id' => $data['external_id'] ?? uniqid('store-'),
                        ];

                        if (filled($data['city_name'] ?? null)) {
                            $stateId = $data['state_name'];
                            $stateResponse = Http::get("https://api.mercadolibre.com/classified_locations/states/{$stateId}");
                            $stateName = $stateResponse->json('name');
                            if (blank($stateName)) {
                                Notification::make()
                                    ->title(__('filament-mercadopago::messages.store.state_resolve_error'))
                                    ->body(__('filament-mercadopago::messages.store.state_resolve_hint'))
                                    ->danger()
                                    ->send();

                                return;
                            }
                            $payload['location'] = [
                                'street_name' => $data['street_name'] ?? 'Sin calle',
                                'street_number' => $data['street_number'] ?? '0',
                                'city_name' => $data['city_name'],
                                'state_name' => $stateName,
                                'latitude' => (float) ($data['latitude'] ?? -34.6037),
                                'longitude' => (float) ($data['longitude'] ?? -58.3816),
                            ];
                        }

                        $response = Http::withToken($credentials->getAccessToken())
                            ->post("https://api.mercadopago.com/users/{$account->mp_user_id}/stores", $payload)->throw();
                        $mpStore = $response->json();
                        Store::create([
                            'account_id' => $account->id, 'mp_store_id' => $mpStore['id'],
                            'name' => $data['name'], 'external_id' => $mpStore['external_id'] ?? $data['external_id'] ?? null, 'raw_payload' => $mpStore,
                        ]);
                    }),

                Action::make('sync_stores')
                    ->label(__('filament-mercadopago::messages.store.sync'))
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        $credentials = app(CredentialResolverInterface::class)->resolve();
                        $account = MercadoPagoAccount::query()->where('mp_user_id', $credentials->getMpUserId())->first();
                        if (! $account) {
                            Notification::make()->title(__('filament-mercadopago::messages.store.no_account'))->danger()->send();

                            return;
                        }
                        $response = Http::withToken($credentials->getAccessToken())
                            ->get("https://api.mercadopago.com/users/{$account->mp_user_id}/stores")->throw();
                        $stores = $response->json('stores', $response->json() ?? []);
                        $count = 0;
                        foreach ($stores as $s) {
                            Store::updateOrCreate(['mp_store_id' => $s['id']],
                                ['account_id' => $account->id, 'name' => $s['name'] ?? 'Sin nombre', 'external_id' => $s['external_id'] ?? null, 'raw_payload' => $s]);
                            $count++;
                        }
                        Notification::make()->title("{$count} sucursales sincronizadas.")->success()->send();
                    }),
            ])
            ->recordActions([
                EditAction::make('edit_store')
                    ->slideOver()->modalWidth(Width::TwoExtraLarge)
                    ->form([
                        Section::make(__('filament-mercadopago::messages.store.basic_info_section'))->columns(2)->schema([
                            TextInput::make('name')->label(__('filament-mercadopago::messages.store.column_name'))->required()->maxLength(255),
                            TextInput::make('external_id')->label(__('filament-mercadopago::messages.store.column_external_id'))->maxLength(255),
                        ]),
                        Section::make(__('filament-mercadopago::messages.store.location_section'))->columns(2)->schema([
                            TextInput::make('street_name')->label(__('filament-mercadopago::messages.store.street'))->maxLength(255),
                            TextInput::make('street_number')->label(__('filament-mercadopago::messages.store.street_number'))->maxLength(20),
                            Select::make('state_name')
                                ->label(__('filament-mercadopago::messages.store.province'))
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('city_name', null))
                                ->options(fn () => collect(
                                    Http::get('https://api.mercadolibre.com/classified_locations/countries/AR')->json('states')
                                )->pluck('name', 'id')),
                            Select::make('city_name')
                                ->label(__('filament-mercadopago::messages.store.city'))
                                ->searchable()
                                ->disabled(fn (callable $get) => ! $get('state_name'))
                                ->options(function (callable $get) {
                                    $stateId = $get('state_name');
                                    if (! $stateId) {
                                        return [];
                                    }

                                    return collect(
                                        Http::get("https://api.mercadolibre.com/classified_locations/states/{$stateId}")->json('cities')
                                    )->pluck('name', 'name');
                                }),
                            TextInput::make('latitude')->label(__('filament-mercadopago::messages.store.latitude'))->numeric()->step(0.000001)->reactive(),
                            TextInput::make('longitude')->label(__('filament-mercadopago::messages.store.longitude'))->numeric()->step(0.000001)->reactive(),
                        ]),
                        ViewField::make('map_picker_edit')->view('filament-mercadopago::forms.map-picker'),
                    ])
                    ->mutateRecordDataUsing(function (array $data, $record) {
                        $credentials = app(CredentialResolverInterface::class)->resolve();
                        $account = MercadoPagoAccount::query()
                            ->where('mp_user_id', $credentials->getMpUserId())->first();

                        $payload = [
                            'name' => $data['name'],
                        ];

                        if (filled($data['external_id'] ?? null) && blank($record->external_id ?? null)) {
                            $payload['external_id'] = $data['external_id'];
                        }

                        if (filled($data['city_name'] ?? null)) {
                            $stateId = $data['state_name'];
                            $stateResponse = Http::get("https://api.mercadolibre.com/classified_locations/states/{$stateId}");
                            $stateName = $stateResponse->json('name');
                            if (blank($stateName)) {
                                Notification::make()
                                    ->title(__('filament-mercadopago::messages.store.state_resolve_error'))
                                    ->body(__('filament-mercadopago::messages.store.state_resolve_hint'))
                                    ->danger()
                                    ->send();

                                return $data;
                            }
                            $payload['location'] = [
                                'street_name' => $data['street_name'] ?? 'Sin calle',
                                'street_number' => $data['street_number'] ?? '0',
                                'city_name' => $data['city_name'],
                                'state_name' => $stateName,
                                'latitude' => (float) ($data['latitude'] ?? -34.6037),
                                'longitude' => (float) ($data['longitude'] ?? -58.3816),
                            ];
                        }

                        Http::withToken($credentials->getAccessToken())
                            ->put("https://api.mercadopago.com/users/{$account->mp_user_id}/stores/{$record->mp_store_id}", $payload)->throw();

                        return $data;
                    }),
                DeleteAction::make('delete_store')
                    ->before(function ($record) {
                        if ($record->mp_store_id) {
                            try {
                                $credentials = app(CredentialResolverInterface::class)->resolve();
                                $account = MercadoPagoAccount::query()
                                    ->where('mp_user_id', $credentials->getMpUserId())->first();
                                Http::withToken($credentials->getAccessToken())
                                    ->delete("https://api.mercadopago.com/users/{$account->mp_user_id}/stores/{$record->mp_store_id}")->throw();
                            } catch (\Throwable $e) {
                                report($e);
                            }
                        }
                    }),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-mercadopago::messages.store.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.store.title');
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoCluster::class;
    }
}
