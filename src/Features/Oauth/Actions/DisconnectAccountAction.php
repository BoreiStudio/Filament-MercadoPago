<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;

class DisconnectAccountAction
{
    public function execute(MercadoPagoAccount $account): void
    {
        $account->update([
            'status' => 'disconnected',
            'access_token' => null,
            'refresh_token' => null,
            'last_refreshed_at' => null,
        ]);
    }
}
