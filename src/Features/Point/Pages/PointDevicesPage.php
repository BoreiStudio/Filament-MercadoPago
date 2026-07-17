<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Point\Pages;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoCluster;
use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use MercadoPago\Client\Point\PointClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPSearchRequest;

class PointDevicesPage extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected string $view = 'filament-mercadopago::table-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(PointDevice::query())
            ->columns([
                TextColumn::make('device_id')->label(__('filament-mercadopago::messages.point.column_device_id'))->searchable(),
                TextColumn::make('model')->label(__('filament-mercadopago::messages.point.column_model')),
                TextColumn::make('operating_mode')->label(__('filament-mercadopago::messages.point.column_mode')),
                TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('pos.name')->label(__('filament-mercadopago::messages.point.column_pos')),
                TextColumn::make('created_at')->label(__('filament-mercadopago::messages.point.column_registered'))->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                Action::make('sync_point')
                    ->label(__('filament-mercadopago::messages.point.sync'))
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        $credentials = app(CredentialResolverInterface::class)->resolve();
                        $account = MercadoPagoAccount::query()->where('mp_user_id', $credentials->getMpUserId())->first();
                        MercadoPagoConfig::setAccessToken($credentials->getAccessToken());
                        $client = new PointClient;
                        $devices = $client->getDevices(new MPSearchRequest(0, 50));
                        $count = 0;
                        foreach ($devices->data ?? [] as $device) {
                            PointDevice::updateOrCreate(['device_id' => $device->id],
                                ['account_id' => $account->id, 'model' => $device->model ?? null, 'operating_mode' => $device->operating_mode ?? null, 'status' => $device->status ?? 'active', 'raw_payload' => json_decode(json_encode($device), true)]);
                            $count++;
                        }
                        Notification::make()->title("{$count} dispositivos sincronizados.")->success()->send();
                    }),
            ])
            ->recordActions([
                ViewAction::make('view_point')
                    ->slideOver()->modalWidth(Width::TwoExtraLarge)
                    ->form(fn ($record) => [
                        Section::make(__('filament-mercadopago::messages.point.view_section'))->columns(2)->schema([
                            TextInput::make('device_id')->label(__('filament-mercadopago::messages.point.column_device_id'))->disabled(),
                            TextInput::make('model')->label(__('filament-mercadopago::messages.point.column_model'))->disabled(),
                            TextInput::make('operating_mode')->label(__('filament-mercadopago::messages.point.operating_mode'))->disabled(),
                            TextInput::make('status')->label(__('filament-mercadopago::messages.point.status'))->disabled(),
                            TextInput::make('pos.name')->label(__('filament-mercadopago::messages.point.column_pos'))->disabled(),
                        ]),
                    ])
                    ->modalSubmitAction(false)->modalCancelActionLabel(__('filament-mercadopago::messages.point.close'))
                    ->extraModalFooterActions([
                        Action::make('charge_point')
                            ->label(__('filament-mercadopago::messages.point.charge'))->color('success')->icon('heroicon-o-credit-card')
                            ->form([
                                Section::make(__('filament-mercadopago::messages.point.charge_section'))->columns(2)->schema([
                                    TextInput::make('amount')->label(__('filament-mercadopago::messages.point.amount'))->required()->numeric()->minValue(0.01)->prefix('$'),
                                    TextInput::make('external_reference')->label(__('filament-mercadopago::messages.point.external_reference'))->maxLength(255),
                                    Select::make('pos_id')->label(__('filament-mercadopago::messages.point.select_pos'))->options(PosTerminal::pluck('name', 'id'))->searchable(),
                                ]),
                            ])
                            ->action(function ($record, array $data) {
                                $credentials = app(CredentialResolverInterface::class)->resolve();
                                MercadoPagoConfig::setAccessToken($credentials->getAccessToken());
                                $client = new PointClient;
                                $client->createPaymentIntent($record->device_id, [
                                    'amount' => (float) $data['amount'], 'description' => $data['external_reference'] ?? 'Cobro Point',
                                ]);
                                if (! empty($data['pos_id'])) {
                                    $record->update(['pos_id' => $data['pos_id']]);
                                }
                                Notification::make()->title(__('filament-mercadopago::messages.point.charge_sent'))->success()->send();
                            }),
                        Action::make('link_pos')
                            ->label(__('filament-mercadopago::messages.point.link_pos'))->icon('heroicon-o-link')
                            ->form([Select::make('pos_id')->label('Caja')->options(PosTerminal::pluck('name', 'id'))->required()->searchable()])
                            ->action(function ($record, array $data) {
                                $record->update(['pos_id' => $data['pos_id']]);
                                Notification::make()->title(__('filament-mercadopago::messages.point.linked'))->success()->send();
                            }),
                    ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-mercadopago::messages.point.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.point.title');
    }

    public static function getNavigationSort(): ?int
    {
        return 50;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoCluster::class;
    }
}
