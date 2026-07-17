<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Point\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use MercadoPago\Client\Point\PointClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPSearchRequest;

class ListPointDevices extends ListRecords
{
    protected static string $resource = \BoreiStudio\FilamentMercadoPago\Features\Point\Resources\PointDeviceResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_id')
                    ->label('Device ID')
                    ->searchable(),

                TextColumn::make('model')
                    ->label('Modelo'),

                TextColumn::make('operating_mode')
                    ->label('Modo'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),

                TextColumn::make('pos.name')
                    ->label('Caja vinculada'),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (PointDevice $record) => PointDeviceResource::getUrl('view', ['record' => $record]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sincronizar dispositivos')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $credentials = app(CredentialResolverInterface::class)->resolve();
                    $account = MercadoPagoAccount::query()
                        ->where('mp_user_id', $credentials->getMpUserId())
                        ->first();

                    MercadoPagoConfig::setAccessToken($credentials->getAccessToken());

                    $client = new PointClient;
                    $devices = $client->getDevices(new MPSearchRequest(0, 50));

                    $count = 0;

                    foreach ($devices->data ?? [] as $device) {
                        PointDevice::updateOrCreate(
                            ['device_id' => $device->id],
                            [
                                'account_id' => $account->id,
                                'model' => $device->model ?? null,
                                'operating_mode' => $device->operating_mode ?? null,
                                'status' => $device->status ?? 'active',
                                'raw_payload' => json_decode(json_encode($device), true),
                            ]
                        );
                        $count++;
                    }

                    Notification::make()
                        ->title("{$count} dispositivos sincronizados.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
