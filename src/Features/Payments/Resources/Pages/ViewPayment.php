<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Payments\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\SyncPaymentFromApiAction;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Resources\PaymentResource;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Actions\CreateRefundAction;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Models\Refund;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalle del pago')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('mp_payment_id')
                            ->label('ID MP'),

                        TextEntry::make('preference_id')
                            ->label('Preference ID'),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (Payment $record) => $record->statusColor()),

                        TextEntry::make('transaction_amount')
                            ->label('Monto')
                            ->money('ARS'),

                        TextEntry::make('currency_id')
                            ->label('Moneda'),

                        TextEntry::make('payment_type_id')
                            ->label('Tipo de pago'),

                        TextEntry::make('payment_method_id')
                            ->label('Método de pago'),

                        TextEntry::make('payer_email')
                            ->label('Email del pagador'),

                        TextEntry::make('external_reference')
                            ->label('Referencia externa'),

                        TextEntry::make('source')
                            ->label('Origen'),

                        TextEntry::make('paid_at')
                            ->label('Pagado el')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                Section::make('Reembolsos')
                    ->visible(fn (Payment $record) => $record->isRefunded() || $record->isPartiallyRefunded())
                    ->schema([
                        TextEntry::make('refunds_sum_amount')
                            ->label('Total reembolsado')
                            ->money('ARS'),
                    ]),

                Section::make('Payload completo (debug)')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('raw_payload')
                            ->label('')
                            ->formatStateUsing(fn ($state) => '<pre>'.json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre>')
                            ->html(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refund')
                ->label('Reembolsar')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (Payment $record) => $record->isApproved())
                ->requiresConfirmation()
                ->modalHeading('Reembolsar pago')
                ->modalDescription(fn (Payment $record) => $this->getRefundModalDescription($record))
                ->form(fn (Payment $record) => $this->getRefundForm($record))
                ->action(function (Payment $record, array $data, CreateRefundAction $action) {
                    $amount = $data['amount'] ?? null;

                    try {
                        $action->execute($record, $amount);

                        $this->refreshFormData(['status']);

                        Notification::make()
                            ->title('Reembolso procesado correctamente.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al reembolsar: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('sync')
                ->label('Resincronizar')
                ->icon('heroicon-o-arrow-path')
                ->action(function (SyncPaymentFromApiAction $action) {
                    $record = $this->getRecord();

                    try {
                        $action->execute($record->mp_payment_id);
                        $this->refreshFormData(['status', 'status_detail', 'raw_payload', 'paid_at']);

                        Notification::make()
                            ->title('Pago sincronizado correctamente.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al sincronizar: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    private function getRefundModalDescription(Payment $record): string
    {
        $totalRefunded = (float) Refund::where('payment_id', $record->id)->sum('amount');
        $available = (float) $record->transaction_amount - $totalRefunded;

        return 'Monto disponible para reembolsar: $'.number_format($available, 2, ',', '.');
    }

    private function getRefundForm(Payment $record): array
    {
        $totalRefunded = (float) Refund::where('payment_id', $record->id)->sum('amount');
        $available = (float) $record->transaction_amount - $totalRefunded;

        return [
            TextInput::make('amount')
                ->label('Monto a reembolsar (dejá vacío para reembolso total)')
                ->numeric()
                ->minValue(0.01)
                ->maxValue($available)
                ->placeholder('$'.number_format($available, 2, ',', '.')),
        ];
    }
}
