<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Point\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Features\Point\Resources\PointDeviceResource;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use MercadoPago\Client\Point\PointClient;
use MercadoPago\MercadoPagoConfig;

class ViewPointDevice extends ViewRecord
{
    protected static string $resource = PointDeviceResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dispositivo Point')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('device_id')
                            ->label('Device ID'),

                        TextEntry::make('model')
                            ->label('Modelo'),

                        TextEntry::make('operating_mode')
                            ->label('Modo de operación'),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),

                        TextEntry::make('pos.name')
                            ->label('Caja vinculada'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        /** @var PointDevice $record */
        $record = $this->getRecord();

        return [
            Action::make('charge')
                ->label('Cobrar')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->form([
                    TextInput::make('amount')
                        ->label('Monto')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->prefix('$'),

                    TextInput::make('external_reference')
                        ->label('Referencia externa')
                        ->maxLength(255),

                    Select::make('pos_id')
                        ->label('Caja')
                        ->options(PosTerminal::pluck('name', 'id'))
                        ->searchable(),
                ])
                ->action(function (array $data) use ($record) {
                    $credentials = app(CredentialResolverInterface::class)->resolve();

                    MercadoPagoConfig::setAccessToken($credentials->getAccessToken());

                    $client = new PointClient;

                    try {
                        $intent = $client->createPaymentIntent(
                            $record->device_id,
                            [
                                'amount' => (float) $data['amount'],
                                'description' => $data['external_reference'] ?? 'Cobro Point',
                            ]
                        );

                        if (! empty($data['pos_id'])) {
                            $record->update(['pos_id' => $data['pos_id']]);
                        }

                        $this->refreshFormData(['device_id', 'model']);

                        Notification::make()
                            ->title('Cobro enviado a la terminal. Esperando pago...')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('link_pos')
                ->label('Vincular a Caja')
                ->icon('heroicon-o-link')
                ->form([
                    Select::make('pos_id')
                        ->label('Caja')
                        ->options(PosTerminal::pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data) use ($record) {
                    $record->update(['pos_id' => $data['pos_id']]);

                    Notification::make()
                        ->title('Dispositivo vinculado a la caja.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
