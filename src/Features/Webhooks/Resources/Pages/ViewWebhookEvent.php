<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Jobs\ProcessPaymentWebhookJob;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources\WebhookEventResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewWebhookEvent extends ViewRecord
{
    protected static string $resource = WebhookEventResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalle del evento')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id'),

                        TextEntry::make('topic')
                            ->label('Topic'),

                        TextEntry::make('mp_resource_id')
                            ->label('Resource ID'),

                        TextEntry::make('signature_valid')
                            ->label('Firma válida')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Sí' : 'No'),

                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'processed' => 'success',
                                'pending' => 'warning',
                                'error' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('error')
                            ->label('Error'),

                        TextEntry::make('created_at')
                            ->label('Recibido')
                            ->dateTime('d/m/Y H:i:s'),

                        TextEntry::make('processed_at')
                            ->label('Procesado')
                            ->dateTime('d/m/Y H:i:s'),
                    ]),

                Section::make('Payload crudo')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('raw_payload')
                            ->label('')
                            ->formatStateUsing(fn ($state) => '<pre>'.json_encode(json_decode($state), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre>')
                            ->html(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocess')
                ->label('Reprocesar')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $record = $this->getRecord();

                    dispatch(new ProcessPaymentWebhookJob($record));

                    Notification::make()
                        ->title('Evento encolado para reprocesar.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
