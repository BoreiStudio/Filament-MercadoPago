<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Payments\Pages;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoCluster;
use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\CreatePreferenceAction;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\SyncPaymentFromApiAction;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Actions\CreateRefundAction;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Models\Refund;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Http;

class PaymentsPage extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected string $view = 'filament-mercadopago::table-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(Payment::query())
            ->columns([
                TextColumn::make('mp_payment_id')->label(__('filament-mercadopago::messages.payment.column_id'))->searchable()->sortable(),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success', 'pending', 'in_process' => 'warning',
                        'rejected', 'cancelled' => 'danger', default => 'gray',
                    })->sortable(),
                TextColumn::make('transaction_amount')->label(__('filament-mercadopago::messages.payment.column_amount'))->money('ARS')->sortable(),
                TextColumn::make('payment_method_id')->label(__('filament-mercadopago::messages.payment.column_method'))->searchable(),
                TextColumn::make('payer_email')->label(__('filament-mercadopago::messages.payment.column_payer'))->searchable(),
                TextColumn::make('external_reference')->label(__('filament-mercadopago::messages.payment.column_reference'))->searchable(),
                TextColumn::make('created_at')->label(__('filament-mercadopago::messages.payment.column_created'))->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'approved' => __('filament-mercadopago::messages.payment.status_approved'), 'pending' => __('filament-mercadopago::messages.payment.status_pending'),
                    'in_process' => __('filament-mercadopago::messages.payment.status_in_process'), 'rejected' => __('filament-mercadopago::messages.payment.status_rejected'),
                    'cancelled' => __('filament-mercadopago::messages.payment.status_cancelled'), 'refunded' => __('filament-mercadopago::messages.payment.status_refunded'),
                    'partially_refunded' => __('filament-mercadopago::messages.payment.status_partially_refunded'),
                ]),
                Filter::make('created_at')->form([
                    DatePicker::make('from'),
                    DatePicker::make('until'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                Action::make('create_preference')
                    ->slideOver()->modalWidth(Width::TwoExtraLarge)
                    ->label(__('filament-mercadopago::messages.payment.new'))
                    ->form([
                        Section::make(__('filament-mercadopago::messages.payment.products_section'))->schema([
                            Repeater::make('items')->schema([
                                TextInput::make('title')->label(__('filament-mercadopago::messages.payment.product'))->required(),
                                TextInput::make('quantity')->label(__('filament-mercadopago::messages.payment.quantity'))->numeric()->default(1)->required(),
                                TextInput::make('unit_price')->label(__('filament-mercadopago::messages.payment.unit_price'))->numeric()->required()->prefix('$'),
                            ])->defaultItems(1)->required()->columns(3),
                        ]),
                        TextInput::make('external_reference')->label(__('filament-mercadopago::messages.payment.external_reference'))->maxLength(255),
                    ])
                    ->action(function (array $data, CreatePreferenceAction $createPreference) {
                        $result = $createPreference->execute(items: $data['items'], externalReference: $data['external_reference'] ?? uniqid('pay-'));
                        Notification::make()->title(__('filament-mercadopago::messages.payment.preference_created'))->success()->send();

                        return redirect()->away($result['init_point']);
                    }),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label(__('filament-mercadopago::messages.payment.cancel'))
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'in_process']))
                    ->requiresConfirmation()
                    ->action(function ($record, CredentialResolverInterface $credentials) {
                        if (filled($record->mp_payment_id)) {
                            Http::withToken($credentials->resolve()->getAccessToken())
                                ->put("https://api.mercadopago.com/v1/payments/{$record->mp_payment_id}", ['status' => 'cancelled'])
                                ->throw();
                        }
                        $record->update(['status' => 'cancelled']);
                        Notification::make()->title(__('filament-mercadopago::messages.payment.cancelled'))->success()->send();
                    }),
                ViewAction::make()
                    ->slideOver()->modalWidth(Width::TwoExtraLarge)
                    ->form(fn ($record) => [
                        Section::make(__('filament-mercadopago::messages.payment.detail_section'))->columns(2)->schema([
                            TextInput::make('mp_payment_id')->label(__('filament-mercadopago::messages.payment.column_id'))->disabled(),
                            TextInput::make('preference_id')->label(__('filament-mercadopago::messages.payment.preference_id'))->disabled(),
                            TextInput::make('status')->label(__('filament-mercadopago::messages.payment.status_label'))->disabled(),
                            TextInput::make('transaction_amount')->label(__('filament-mercadopago::messages.payment.column_amount'))->disabled()->prefix('$'),
                            TextInput::make('currency_id')->label(__('filament-mercadopago::messages.payment.currency'))->disabled(),
                            TextInput::make('payment_type_id')->label(__('filament-mercadopago::messages.payment.type'))->disabled(),
                            TextInput::make('payment_method_id')->label(__('filament-mercadopago::messages.payment.column_method'))->disabled(),
                            TextInput::make('payer_email')->label(__('filament-mercadopago::messages.payment.email'))->disabled(),
                            TextInput::make('external_reference')->label(__('filament-mercadopago::messages.payment.column_reference'))->disabled(),
                            TextInput::make('source')->label(__('filament-mercadopago::messages.payment.source'))->disabled(),
                            TextInput::make('paid_at')->label(__('filament-mercadopago::messages.payment.paid_at'))->disabled(),
                            TextInput::make('created_at')->label(__('filament-mercadopago::messages.payment.column_created'))->disabled(),
                        ]),
                    ])
                    ->modalSubmitAction(false)->modalCancelActionLabel(__('filament-mercadopago::messages.payment.close'))
                    ->extraModalFooterActions([
                        Action::make('refund_from_view')
                            ->label(__('filament-mercadopago::messages.payment.refund'))->color('warning')->icon('heroicon-o-arrow-uturn-left')
                            ->visible(fn ($record) => $record->isApproved())->requiresConfirmation()
                            ->form(fn ($record) => [
                                TextInput::make('amount')
                                    ->label('Monto (dejá vacío para reembolso total)')
                                    ->numeric()->minValue(0.01)
                                    ->maxValue(fn () => (float) $record->transaction_amount - (float) Refund::where('payment_id', $record->id)->sum('amount'))
                                    ->placeholder(fn ($record) => '$'.number_format((float) $record->transaction_amount - (float) Refund::where('payment_id', $record->id)->sum('amount'), 2)),
                            ])
                            ->action(function ($record, array $data, CreateRefundAction $action) {
                                $action->execute($record, $data['amount'] ?? null);
                                Notification::make()->title(__('filament-mercadopago::messages.payment.refund_processed'))->success()->send();
                            }),
                        Action::make('sync_from_view')
                            ->label(__('filament-mercadopago::messages.payment.resync'))->icon('heroicon-o-arrow-path')
                            ->action(function ($record, SyncPaymentFromApiAction $action) {
                                $action->execute($record->mp_payment_id);
                                Notification::make()->title(__('filament-mercadopago::messages.payment.synced'))->success()->send();
                            }),
                    ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-mercadopago::messages.payment.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.payment.title');
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoCluster::class;
    }
}
