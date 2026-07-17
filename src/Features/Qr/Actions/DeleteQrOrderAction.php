<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Qr\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Qr\Models\QrOrder;
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
            $response = Http::withToken($credentials->getAccessToken())
                ->delete("https://api.mercadopago.com/instore/orders/qr/seller/collectors/{$qrOrder->account->mp_user_id}/pos/{$qrOrder->pos->external_id}/qrs");

            $response->throw();
        }

        $qrOrder->update(['status' => 'closed']);
    }
}
