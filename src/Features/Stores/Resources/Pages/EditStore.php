<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\StoreResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class EditStore extends EditRecord
{
    protected static string $resource = StoreResource::class;

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                TextInput::make('external_id')
                    ->label('ID externo')
                    ->maxLength(255),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Store $record */
        $record = $this->getRecord();

        if ($record->mp_store_id) {
            $credentials = app(CredentialResolverInterface::class)->resolve();

            Http::withToken($credentials->getAccessToken())
                ->put("https://api.mercadopago.com/stores/{$record->mp_store_id}", [
                    'name' => $data['name'],
                    'external_id' => $data['external_id'] ?? null,
                ])
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
                    /** @var Store $record */
                    $record = $this->getRecord();
                    $credentials = app(CredentialResolverInterface::class)->resolve();

                    $response = Http::withToken($credentials->getAccessToken())
                        ->get("https://api.mercadopago.com/stores/{$record->mp_store_id}");

                    $response->throw();
                    $mpStore = $response->json();

                    $record->update([
                        'name' => $mpStore['name'] ?? $record->name,
                        'external_id' => $mpStore['external_id'] ?? $record->external_id,
                        'business_hours' => $mpStore['business_hours'] ?? null,
                        'location' => $mpStore['location'] ?? null,
                        'raw_payload' => $mpStore,
                    ]);

                    $this->refreshFormData(['name', 'external_id']);

                    Notification::make()
                        ->title('Sucursal sincronizada.')
                        ->success()
                        ->send();
                }),

            DeleteAction::make()
                ->before(function () {
                    /** @var Store $record */
                    $record = $this->getRecord();

                    if ($record->mp_store_id) {
                        try {
                            $credentials = app(CredentialResolverInterface::class)->resolve();

                            Http::withToken($credentials->getAccessToken())
                                ->delete("https://api.mercadopago.com/stores/{$record->mp_store_id}")
                                ->throw();
                        } catch (\Throwable $e) {
                            report($e);
                        }
                    }
                }),
        ];
    }
}
