<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\Pages;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Resources\PosResource;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;

class CreatePos extends CreateRecord
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
                    ->helperText('Primero creá una Sucursal si no hay ninguna disponible.')
                    ->searchable(),

                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                TextInput::make('external_id')
                    ->label('ID externo')
                    ->required()
                    ->maxLength(40)
                    ->rule('alpha_num')
                    ->helperText('Solo letras y números, sin espacios ni guiones. Máx. 40 caracteres.'),

                Select::make('category')
                    ->label('Categoría')
                    ->options([
                        621102 => 'Gastronomía',
                    ])
                    ->helperText('Opcional. Si no se especifica, se asigna una genérica.'),

                Toggle::make('fixed_amount')
                    ->label('Monto fijo')
                    ->default(true),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $credentials = app(CredentialResolverInterface::class)->resolve();
        $account = MercadoPagoAccount::query()
            ->where('mp_user_id', $credentials->getMpUserId())
            ->first();

        $store = Store::findOrFail($data['store_id']);

        $payload = [
            'name' => $data['name'],
            'external_id' => $data['external_id'],
            'store_id' => $store->mp_store_id,
            'fixed_amount' => $data['fixed_amount'] ?? true,
        ];

        $externalStoreId = $store->raw_payload['external_id'] ?? $store->external_id;
        if (filled($externalStoreId)) {
            $payload['external_store_id'] = $externalStoreId;
        }

        if (filled($data['category'] ?? null)) {
            $payload['category'] = (int) $data['category'];
        }

        $response = Http::withToken($credentials->getAccessToken())
            ->post('https://api.mercadopago.com/pos', $payload);

        $response->throw();
        $mpPos = $response->json();

        $data['account_id'] = $account->id;
        $data['mp_pos_id'] = $mpPos['id'];
        $data['qr_image_url'] = $mpPos['qr']['image'] ?? $mpPos['qr_image'] ?? null;
        $data['raw_payload'] = $mpPos;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
