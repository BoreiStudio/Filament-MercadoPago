<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\PosResource;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class EditPos extends EditRecord
{
    protected static string $resource = PosResource::class;

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('store_id')
                    ->label('Sucursal')
                    ->options(Store::pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                TextInput::make('external_id')
                    ->label('ID externo')
                    ->maxLength(255),

                Select::make('category')
                    ->label('Categoría')
                    ->options([
                        'PDV' => 'PDV',
                        'STANDALONE' => 'Standalone',
                        'OTHER' => 'Otro',
                    ]),

                Toggle::make('fixed_amount')
                    ->label('Monto fijo'),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $credentials = app(CredentialResolverInterface::class)->resolve();

        $payload = [
            'name' => $data['name'],
            'external_id' => $data['external_id'] ?? null,
            'fixed_amount' => $data['fixed_amount'] ?? false,
            'category' => $data['category'] ?? null,
        ];

        if (isset($data['store_id'])) {
            $store = Store::find($data['store_id']);
            if ($store) {
                $payload['store_id'] = $store->mp_store_id;
            }
        }

        if ($record->mp_pos_id) {
            Http::withToken($credentials->getAccessToken())
                ->put("https://api.mercadopago.com/pos/{$record->mp_pos_id}", $payload)
                ->throw();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sincronizar desde MP')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $record = $this->getRecord();
                    $credentials = app(CredentialResolverInterface::class)->resolve();

                    $response = Http::withToken($credentials->getAccessToken())
                        ->get("https://api.mercadopago.com/pos/{$record->mp_pos_id}");

                    $response->throw();
                    $mpPos = $response->json();

                    $storeId = null;
                    if (! empty($mpPos['store_id'])) {
                        $store = Store::where('mp_store_id', $mpPos['store_id'])->first();
                        $storeId = $store?->id;
                    }

                    $record->update([
                        'name' => $mpPos['name'] ?? $record->name,
                        'store_id' => $storeId ?? $record->store_id,
                        'external_id' => $mpPos['external_id'] ?? $record->external_id,
                        'fixed_amount' => $mpPos['fixed_amount'] ?? $record->fixed_amount,
                        'category' => $mpPos['category'] ?? $record->category,
                        'raw_payload' => $mpPos,
                    ]);

                    $this->refreshFormData(['name', 'store_id', 'external_id', 'fixed_amount', 'category']);

                    Notification::make()
                        ->title('Caja sincronizada.')
                        ->success()
                        ->send();
                }),

            DeleteAction::make()
                ->before(function () {
                    $record = $this->getRecord();

                    if ($record->mp_pos_id) {
                        try {
                            $credentials = app(CredentialResolverInterface::class)->resolve();

                            Http::withToken($credentials->getAccessToken())
                                ->delete("https://api.mercadopago.com/pos/{$record->mp_pos_id}")
                                ->throw();
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }
                }),
        ];
    }
}
