<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Stores\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Support\Facades\Http;

class SyncStoresFromApiAction
{
    public function __construct(
        private readonly CredentialResolverInterface $credentialResolver,
    ) {}

    public function execute(): int
    {
        $credentials = $this->credentialResolver->resolve();
        $account = MercadoPagoAccount::query()
            ->where('mp_user_id', $credentials->getMpUserId())
            ->first();

        if (! $account) {
            throw new \RuntimeException('No se encontró la cuenta de Mercado Pago.');
        }

        $response = Http::withToken($credentials->getAccessToken())
            ->get("https://api.mercadopago.com/users/{$account->mp_user_id}/stores");

        $response->throw();

        $stores = $response->json('stores', $response->json() ?? []);

        $count = 0;

        foreach ($stores as $storeData) {
            Store::updateOrCreate(
                ['mp_store_id' => $storeData['id']],
                [
                    'account_id' => $account->id,
                    'name' => $storeData['name'] ?? 'Sin nombre',
                    'external_id' => $storeData['external_id'] ?? null,
                    'business_hours' => $storeData['business_hours'] ?? null,
                    'location' => $storeData['location'] ?? null,
                    'raw_payload' => $storeData,
                ]
            );
            $count++;
        }

        return $count;
    }
}
