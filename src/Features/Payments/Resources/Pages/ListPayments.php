<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Payments\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Features\Payments\Resources\PaymentResource;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mp_payment_id')
                    ->label('ID MP')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending', 'in_process' => 'warning',
                        'rejected', 'cancelled' => 'danger',
                        'refunded', 'partially_refunded' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('transaction_amount')
                    ->label('Monto')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('payment_method_id')
                    ->label('Método')
                    ->searchable(),

                TextColumn::make('payer_email')
                    ->label('Pagador')
                    ->searchable(),

                TextColumn::make('external_reference')
                    ->label('Referencia')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'approved' => 'Aprobado',
                        'pending' => 'Pendiente',
                        'in_process' => 'En proceso',
                        'rejected' => 'Rechazado',
                        'cancelled' => 'Cancelado',
                        'refunded' => 'Reembolsado',
                        'partially_refunded' => 'Reemb. parcial',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
