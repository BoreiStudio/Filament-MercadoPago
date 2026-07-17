<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Qr\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Models\QrOrder;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Support\Facades\Http;

class CreateQrOrderAction
{
    public function __construct(
        private readonly CredentialResolverInterface $credentialResolver,
    ) {}

    public function execute(
        PosTerminal $pos,
        array $items,
        string $externalReference,
        ?string $title = null,
    ): QrOrder {
        $credentials = $this->credentialResolver->resolve();

        $account = MercadoPagoAccount::query()
            ->where('mp_user_id', $credentials->getMpUserId())
            ->first();

        $totalAmount = array_sum(array_map(fn ($item) => $item['quantity'] * $item['unit_price'], $items));

        $title ??= 'QR '.$pos->name;

        $response = Http::withToken($credentials->getAccessToken())
            ->put("https://api.mercadopago.com/instore/orders/qr/seller/collectors/{$account->mp_user_id}/pos/{$pos->external_id}/qrs", [
                'external_reference' => $externalReference,
                'title' => $title,
                'description' => $title,
                'total_amount' => $totalAmount,
                'items' => array_map(fn ($item) => [
                    'sku_number' => $item['sku'] ?? null,
                    'category' => $item['category'] ?? 'regular',
                    'title' => $item['title'],
                    'description' => $item['description'] ?? $item['title'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_measure' => $item['unit_measure'] ?? 'unit',
                    'total_amount' => $item['quantity'] * $item['unit_price'],
                ], $items),
                'sponsor' => [
                    'id' => $account->mp_user_id,
                ],
            ]);

        $response->throw();
        $mpOrder = $response->json();

        return QrOrder::create([
            'account_id' => $account->id,
            'pos_id' => $pos->id,
            'mp_order_id' => $mpOrder['order_id'] ?? $mpOrder['id'] ?? null,
            'external_reference' => $externalReference,
            'title' => $title,
            'total_amount' => $totalAmount,
            'items' => $items,
            'status' => 'opened',
            'raw_payload' => $mpOrder,
        ]);
    }
}
