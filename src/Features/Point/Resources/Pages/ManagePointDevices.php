<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Point\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Features\Point\Resources\PointDeviceResource;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use MercadoPago\Client\Point\PointClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPSearchRequest;

class ManagePointDevices extends ManageRecords
{
    protected static string $resource = PointDeviceResource::class;

    public static function getNavigationSort(): ?int
    {
        return 50;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_id')->label('Device ID')->searchable(),
                TextColumn::make('model')->label('Modelo'),
                TextColumn::make('operating_mode')->label('Modo'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('pos.name')->label('Caja vinculada'),
                TextColumn::make('created_at')->label('Registrado')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->form(fn ($record) => [
                        Section::make('Dispositivo Point')->columns(2)->schema([
                            TextInput::make('device_id')->label('Device ID')->disabled(),
                            TextInput::make('model')->label('Modelo')->disabled(),
                            TextInput::make('operating_mode')->label('Modo de operación')->disabled(),
                            TextInput::make('status')->label('Estado')->disabled(),
                            TextInput::make('pos.name')->label('Caja vinculada')->disabled(),
                        ]),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Action::make('charge_point')
                            ->label('Cobrar')
                            ->color('success')
                            ->icon('heroicon-o-credit-card')
                            ->form([
                                Section::make('Nuevo cobro')->columns(2)->schema([
                                    TextInput::make('amount')->label('Monto')->required()->numeric()->minValue(0.01)->prefix('$'),
                                    TextInput::make('external_reference')->label('Referencia externa')->maxLength(255),
                                    Select::make('pos_id')->label('Vincular a caja')->options(PosTerminal::pluck('name', 'id'))->searchable(),
                                ]),
                            ])
                            ->action(function ($record, array $data) {
                                $credentials = app(CredentialResolverInterface::class)->resolve();
                                MercadoPagoConfig::setAccessToken($credentials->getAccessToken());
                                $client = new PointClient;

                                $intent = $client->createPaymentIntent($record->device_id, [
                                    'amount' => (float) $data['amount'],
                                    'description' => $data['external_reference'] ?? 'Cobro Point',
                                ]);

                                if (! empty($data['pos_id'])) {
                                    $record->update(['pos_id' => $data['pos_id']]);
                                }
                                Notification::make()->title('Cobro enviado a la terminal.')->success()->send();
                            }),

                        Action::make('link_pos')
                            ->label('Vincular a Caja')
                            ->icon('heroicon-o-link')
                            ->form([
                                Select::make('pos_id')->label('Caja')->options(PosTerminal::pluck('name', 'id'))->required()->searchable(),
                            ])
                            ->action(function ($record, array $data) {
                                $record->update(['pos_id' => $data['pos_id']]);
                                Notification::make()->title('Dispositivo vinculado.')->success()->send();
                            }),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sincronizar dispositivos')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $credentials = app(CredentialResolverInterface::class)->resolve();
                    $account = MercadoPagoAccount::query()->where('mp_user_id', $credentials->getMpUserId())->first();

                    MercadoPagoConfig::setAccessToken($credentials->getAccessToken());
                    $client = new PointClient;
                    $devices = $client->getDevices(new MPSearchRequest(0, 50));
                    $count = 0;

                    foreach ($devices->data ?? [] as $device) {
                        PointDevice::updateOrCreate(
                            ['device_id' => $device->id],
                            ['account_id' => $account->id, 'model' => $device->model ?? null, 'operating_mode' => $device->operating_mode ?? null, 'status' => $device->status ?? 'active', 'raw_payload' => json_decode(json_encode($device), true)]
                        );
                        $count++;
                    }
                    Notification::make()->title("{$count} dispositivos sincronizados.")->success()->send();
                }),
        ];
    }
}
