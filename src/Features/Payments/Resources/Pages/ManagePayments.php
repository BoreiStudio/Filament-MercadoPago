<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Payments\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\CreatePreferenceAction;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\SyncPaymentFromApiAction;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Resources\PaymentResource;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Actions\CreateRefundAction;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Models\Refund;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;

    public static function getNavigationSort(): ?int
    {
        return 20;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mp_payment_id')->label('ID MP')->searchable()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success', 'pending', 'in_process' => 'warning',
                        'rejected', 'cancelled' => 'danger', default => 'gray',
                    })->sortable(),
                TextColumn::make('transaction_amount')->label('Monto')->money('ARS')->sortable(),
                TextColumn::make('payment_method_id')->label('Método')->searchable(),
                TextColumn::make('payer_email')->label('Pagador')->searchable(),
                TextColumn::make('external_reference')->label('Referencia')->searchable(),
                TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'approved' => 'Aprobado', 'pending' => 'Pendiente',
                    'in_process' => 'En proceso', 'rejected' => 'Rechazado',
                    'cancelled' => 'Cancelado', 'refunded' => 'Reembolsado',
                    'partially_refunded' => 'Reemb. parcial',
                ]),
                Filter::make('created_at')->form([
                    DatePicker::make('from'),
                    DatePicker::make('until'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->form(fn ($record) => [
                        Section::make('Detalle del pago')->columns(2)->schema([
                            TextInput::make('mp_payment_id')->label('ID MP')->disabled(),
                            TextInput::make('preference_id')->label('Preference ID')->disabled(),
                            TextInput::make('status')->label('Estado')->disabled(),
                            TextInput::make('transaction_amount')->label('Monto')->disabled()->prefix('$'),
                            TextInput::make('currency_id')->label('Moneda')->disabled(),
                            TextInput::make('payment_type_id')->label('Tipo')->disabled(),
                            TextInput::make('payment_method_id')->label('Método')->disabled(),
                            TextInput::make('payer_email')->label('Email')->disabled(),
                            TextInput::make('external_reference')->label('Referencia')->disabled(),
                            TextInput::make('source')->label('Origen')->disabled(),
                            TextInput::make('paid_at')->label('Pagado')->disabled(),
                            TextInput::make('created_at')->label('Creado')->disabled(),
                        ]),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Action::make('refund_from_view')
                            ->label('Reembolsar')
                            ->color('warning')
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->visible(fn ($record) => $record->isApproved())
                            ->requiresConfirmation()
                            ->form(fn ($record) => [
                                TextInput::make('amount')
                                    ->label('Monto (dejá vacío para reembolso total)')
                                    ->numeric()->minValue(0.01)
                                    ->maxValue(fn () => (float) $record->transaction_amount - (float) Refund::where('payment_id', $record->id)->sum('amount'))
                                    ->placeholder(fn ($record) => '$'.number_format((float) $record->transaction_amount - (float) Refund::where('payment_id', $record->id)->sum('amount'), 2)),
                            ])
                            ->action(function ($record, array $data, CreateRefundAction $action) {
                                $action->execute($record, $data['amount'] ?? null);
                                Notification::make()->title('Reembolso procesado.')->success()->send();
                            }),

                        Action::make('sync_from_view')
                            ->label('Resincronizar')
                            ->icon('heroicon-o-arrow-path')
                            ->action(function ($record, SyncPaymentFromApiAction $action) {
                                $action->execute($record->mp_payment_id);
                                Notification::make()->title('Pago sincronizado.')->success()->send();
                            }),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth(Width::TwoExtraLarge)
                ->label('Nuevo pago')
                ->form([
                    Section::make('Productos')->schema([
                        Repeater::make('items')->schema([
                            TextInput::make('title')->label('Producto')->required(),
                            TextInput::make('quantity')->label('Cantidad')->numeric()->default(1)->required(),
                            TextInput::make('unit_price')->label('Precio unitario')->numeric()->required()->prefix('$'),
                        ])->defaultItems(1)->required()->columns(3),
                    ]),
                    TextInput::make('external_reference')->label('Referencia externa')->maxLength(255),
                ])
                ->action(function (array $data, CreatePreferenceAction $action) {
                    $result = $action->execute(
                        items: $data['items'],
                        externalReference: $data['external_reference'] ?? uniqid('pay-'),
                    );
                    Notification::make()->title('Preferencia creada.')->success()->send();

                    return redirect()->away($result['init_point']);
                }),
        ];
    }
}
