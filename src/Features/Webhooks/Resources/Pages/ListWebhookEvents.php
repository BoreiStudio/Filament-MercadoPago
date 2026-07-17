<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Resources\WebhookEventResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListWebhookEvents extends ListRecords
{
    protected static string $resource = WebhookEventResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('topic')
                    ->label('Topic')
                    ->searchable(),

                TextColumn::make('mp_resource_id')
                    ->label('Resource ID')
                    ->searchable(),

                TextColumn::make('signature_valid')
                    ->label('Firma')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Válida' : 'Inválida'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'processed' => 'success',
                        'pending' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('error')
                    ->label('Error')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Recibido')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('processed_at')
                    ->label('Procesado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'processed' => 'Procesado',
                        'error' => 'Error',
                    ]),

                SelectFilter::make('signature_valid')
                    ->label('Firma')
                    ->options([
                        '1' => 'Válida',
                        '0' => 'Inválida',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
