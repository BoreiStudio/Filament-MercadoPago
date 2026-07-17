<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Qr\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Models\QrOrder;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Support\Facades\Http;

class DeleteQrOrderAction
{
    public function __construct(
        private readonly CredentialResolverInterface $credentialResolver,
    ) {}

    public function execute(QrOrder $qrOrder): void
    {
        $credentials = $this->credentialResolver->resolve();

        if ($qrOrder->mp_order_id) {
            /** @var PosTerminal|null $pos */
            $pos = $qrOrder->pos;
            /** @var MercadoPagoAccount|null $account */
            $account = $qrOrder->account;

            $response = Http::withToken($credentials->getAccessToken())
                ->delete("https://api.mercadopago.com/instore/orders/qr/seller/collectors/{$account->mp_user_id}/pos/{$pos->external_id}/qrs");

            $response->throw();
        }

        $qrOrder->update(['status' => 'closed']);
    }
}
