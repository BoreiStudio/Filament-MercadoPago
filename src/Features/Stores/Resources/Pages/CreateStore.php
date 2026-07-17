<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Resources\StoreResource;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class CreateStore extends CreateRecord
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
                    ->helperText('Identificador único en tu sistema.')
                    ->maxLength(255),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $credentials = app(CredentialResolverInterface::class)->resolve();
        $account = MercadoPagoAccount::query()
            ->where('mp_user_id', $credentials->getMpUserId())
            ->first();

        $response = Http::withToken($credentials->getAccessToken())
            ->post('https://api.mercadopago.com/stores', [
                'name' => $data['name'],
                'external_id' => $data['external_id'] ?? null,
            ]);

        $response->throw();
        $mpStore = $response->json();

        $data['account_id'] = $account->id;
        $data['mp_store_id'] = $mpStore['id'];
        $data['raw_payload'] = $mpStore;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
