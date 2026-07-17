<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Payments\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Support\Http\MercadoPagoClient;

class CreatePreferenceAction
{
    public function __construct(
        private readonly CredentialResolverInterface $credentialResolver,
        private readonly ?MercadoPagoClient $client = null,
    ) {}

    public function execute(
        array $items,
        string $externalReference,
        ?array $backUrls = null,
        ?string $notificationUrl = null,
        ?MercadoPagoAccount $account = null,
    ): array {
        $credentials = $this->credentialResolver->resolve();

        $client = $this->client ?? new MercadoPagoClient($credentials);

        $preferenceData = [
            'items' => array_map(fn ($item) => [
                'title' => $item['title'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'currency_id' => $item['currency_id'] ?? 'ARS',
            ], $items),
            'external_reference' => $externalReference,
        ];

        if ($backUrls) {
            $preferenceData['back_urls'] = $backUrls;
        }

        if ($notificationUrl) {
            $preferenceData['notification_url'] = $notificationUrl;
        }

        $preference = $client->createPreference($preferenceData);

        $payment = Payment::create([
            'account_id' => $account->id ?? $this->resolveAccountId($credentials),
            'preference_id' => $preference->id,
            'status' => 'pending',
            'transaction_amount' => array_sum(array_column($items, 'unit_price')),
            'external_reference' => $externalReference,
            'source' => 'checkout_pro',
            'raw_payload' => json_decode(json_encode($preference), true),
        ]);

        return [
            'init_point' => $preference->init_point,
            'sandbox_init_point' => $preference->sandbox_init_point ?? null,
            'payment' => $payment,
        ];
    }

    private function resolveAccountId($credentials): ?int
    {
        $account = MercadoPagoAccount::query()
            ->where('mp_user_id', $credentials->getMpUserId())
            ->first();

        return $account?->id;
    }
}
