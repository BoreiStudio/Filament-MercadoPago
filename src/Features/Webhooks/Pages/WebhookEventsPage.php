<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Pages;

use BoreiStudio\FilamentMercadoPago\Clusters\MercadoPagoCluster;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Jobs\ProcessPaymentWebhookJob;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Models\WebhookEvent;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class WebhookEventsPage extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected string $view = 'filament-mercadopago::table-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(WebhookEvent::query())
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('topic')->label(__('filament-mercadopago::messages.webhook.column_topic'))->searchable(),
                TextColumn::make('mp_resource_id')->label(__('filament-mercadopago::messages.webhook.column_resource_id'))->searchable(),
                TextColumn::make('signature_valid')->label(__('filament-mercadopago::messages.webhook.column_signature'))
                    ->badge()->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Válida' : 'Inválida'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'processed' => 'success', 'pending' => 'warning', 'error' => 'danger', default => 'gray',
                    }),
                TextColumn::make('error')->label(__('filament-mercadopago::messages.webhook.column_error'))->searchable()->toggleable(),
                TextColumn::make('created_at')->label(__('filament-mercadopago::messages.webhook.column_received'))->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('processed_at')->label(__('filament-mercadopago::messages.webhook.column_processed'))->dateTime('d/m/Y H:i:s')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => __('filament-mercadopago::messages.payment.status_pending'), 'processed' => 'Procesado', 'error' => 'Error']),
                SelectFilter::make('signature_valid')->label(__('filament-mercadopago::messages.webhook.column_signature'))->options(['1' => 'Válida', '0' => 'Inválida']),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make('view_webhook')
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->form(fn ($record) => [
                        Section::make(__('filament-mercadopago::messages.webhook.detail_section'))->columns(2)->schema([
                            TextInput::make('id')->disabled(),
                            TextInput::make('topic')->label(__('filament-mercadopago::messages.webhook.column_topic'))->disabled(),
                            TextInput::make('mp_resource_id')->label(__('filament-mercadopago::messages.webhook.column_resource_id'))->disabled(),
                            TextInput::make('signature_valid')->label(__('filament-mercadopago::messages.webhook.signature_valid_label'))
                                ->formatStateUsing(fn (bool $state): string => $state ? __('filament-mercadopago::messages.webhook.yes') : __('filament-mercadopago::messages.webhook.no'))->disabled(),
                            TextInput::make('status')->label('Estado')->disabled(),
                            TextInput::make('error')->label(__('filament-mercadopago::messages.webhook.column_error'))->disabled(),
                            TextInput::make('created_at')->label(__('filament-mercadopago::messages.webhook.column_received'))->disabled(),
                            TextInput::make('processed_at')->label(__('filament-mercadopago::messages.webhook.column_processed'))->disabled(),
                        ]),
                        Section::make(__('filament-mercadopago::messages.webhook.raw_payload'))->schema([
                            Textarea::make('raw_payload')->label('')->disabled()->rows(15)
                                ->formatStateUsing(fn ($state) => json_encode(json_decode($state ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                        ]),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Action::make('reprocess')
                            ->label(__('filament-mercadopago::messages.webhook.reprocess'))
                            ->icon('heroicon-o-arrow-path')
                            ->color('warning')
                            ->action(function ($record) {
                                dispatch(new ProcessPaymentWebhookJob($record));
                                Notification::make()->title(__('filament-mercadopago::messages.webhook.reprocess_queued'))->success()->send();
                            }),
                    ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-mercadopago::messages.webhook.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-mercadopago::messages.webhook.title');
    }

    public static function getNavigationSort(): ?int
    {
        return 60;
    }

    public static function getCluster(): ?string
    {
        return MercadoPagoCluster::class;
    }
}
