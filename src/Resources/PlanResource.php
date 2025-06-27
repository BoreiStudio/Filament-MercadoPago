<?php

namespace BoreiStudio\FilamentMercadoPago\Resources;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoPlan;
use BoreiStudio\FilamentMercadoPago\Resources\PlanResource\Pages;
use BoreiStudio\FilamentMercadoPago\Services\MercadoPagoPlanService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions;

class PlanResource extends Resource
{
    protected static ?string $model = MercadoPagoPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Mercado Pago';

    protected static ?string $navigationLabel = 'Planes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255)->label('Nombre del Plan'),
            Forms\Components\Textarea::make('description')->label('Descripción')->rows(3),
            Forms\Components\TextInput::make('amount')->label('Precio')->required()->numeric()->minValue(0),
            Forms\Components\Select::make('currency')
                ->label('Moneda')
                ->options([
                    'ARS' => 'ARS - Peso Argentino',
                ])
                ->default('ARS')
                ->required(),
            Forms\Components\TextInput::make('frequency')
                ->label('Frecuencia')
                ->required()
                ->numeric()
                ->minValue(1)
                ->helperText('Número de unidades para la frecuencia (ej: 1, 3, 6)'),
            Forms\Components\Select::make('frequency_type')
                ->label('Tipo de Frecuencia')
                ->options([
                    'days' => 'Días',
                    'months' => 'Meses',
                    'years' => 'Años',
                ])
                ->default('months')
                ->required(),
            Forms\Components\TextInput::make('repetitions')
                ->label('Repeticiones')
                ->numeric()
                ->minValue(0)
                ->helperText('Cantidad de pagos (0 = indefinido)')
                ->default(0),
            Forms\Components\Toggle::make('status')->label('Activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('ARS', true),
                Tables\Columns\ToggleColumn::make('status')->sortable(),
                Tables\Columns\TextColumn::make('frequency'),
                Tables\Columns\TextColumn::make('frequency_type'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\Action::make('syncWithMercadoPago')
                    ->label('Sincronizar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $service = app(MercadoPagoPlanService::class);

                        try {
                            $plan = $service->syncPlan($record, $record->user_id ?? auth()->id());

                            \Filament\Notifications\Notification::make()
                                ->title('Plan sincronizado')
                                ->success()
                                ->body("Plan sincronizado en Mercado Pago con ID: {$plan->external_id}")
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al sincronizar')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
