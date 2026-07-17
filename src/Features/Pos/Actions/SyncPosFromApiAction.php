<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Pos\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Support\Facades\Http;

class SyncPosFromApiAction
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
            ->get('https://api.mercadopago.com/pos');

        $response->throw();

        $posList = $response->json('results', $response->json() ?? []);

        $count = 0;

        foreach ($posList as $posData) {
            PosTerminal::updateOrCreate(
                ['mp_pos_id' => $posData['id']],
                [
                    'account_id' => $account->id,
                    'name' => $posData['name'] ?? 'Sin nombre',
                    'external_id' => $posData['external_id'] ?? null,
                    'fixed_amount' => $posData['fixed_amount'] ?? false,
                    'category' => $posData['category'] ?? null,
                    'qr_image_url' => $posData['qr']['image'] ?? $posData['qr_image'] ?? null,
                    'raw_payload' => $posData,
                ]
            );
            $count++;
        }

        return $count;
    }
}
