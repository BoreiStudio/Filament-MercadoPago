<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Jobs\ProcessPaymentWebhookJob;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources\WebhookEventResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ManageWebhookEvents extends ManageRecords
{
    protected static string $resource = WebhookEventResource::class;

    public static function getNavigationSort(): ?int
    {
        return 60;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('topic')->label('Topic')->searchable(),
                TextColumn::make('mp_resource_id')->label('Resource ID')->searchable(),
                TextColumn::make('signature_valid')->label('Firma')
                    ->badge()->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Válida' : 'Inválida'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'processed' => 'success', 'pending' => 'warning', 'error' => 'danger', default => 'gray',
                    }),
                TextColumn::make('error')->label('Error')->searchable()->toggleable(),
                TextColumn::make('created_at')->label('Recibido')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('processed_at')->label('Procesado')->dateTime('d/m/Y H:i:s')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pendiente', 'processed' => 'Procesado', 'error' => 'Error']),
                SelectFilter::make('signature_valid')->label('Firma')->options(['1' => 'Válida', '0' => 'Inválida']),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->form(fn ($record) => [
                        Section::make('Detalle del evento')->columns(2)->schema([
                            TextInput::make('id')->disabled(),
                            TextInput::make('topic')->label('Topic')->disabled(),
                            TextInput::make('mp_resource_id')->label('Resource ID')->disabled(),
                            TextInput::make('signature_valid')->label('Firma válida')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Sí' : 'No')->disabled(),
                            TextInput::make('status')->label('Estado')->disabled(),
                            TextInput::make('error')->label('Error')->disabled(),
                            TextInput::make('created_at')->label('Recibido')->disabled(),
                            TextInput::make('processed_at')->label('Procesado')->disabled(),
                        ]),
                        Section::make('Payload crudo')->schema([
                            Textarea::make('raw_payload')->label('')->disabled()->rows(15)
                                ->formatStateUsing(fn ($state) => json_encode(json_decode($state ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
                        ]),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Action::make('reprocess')
                            ->label('Reprocesar')
                            ->icon('heroicon-o-arrow-path')
                            ->color('warning')
                            ->action(function ($record) {
                                dispatch(new ProcessPaymentWebhookJob($record));
                                Notification::make()->title('Evento encolado para reprocesar.')->success()->send();
                            }),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
