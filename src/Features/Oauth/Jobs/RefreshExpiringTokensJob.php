<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Oauth\Jobs;

use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\RefreshAccessTokenAction;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class RefreshExpiringTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(RefreshAccessTokenAction $action): void
    {
        $threshold = config('mercadopago.sync.refresh_token_threshold_days', 15);

        $accounts = MercadoPagoAccount::query()
            ->where('status', 'connected')
            ->whereNotNull('refresh_token')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($threshold))
            ->cursor();

        foreach ($accounts as $account) {
            try {
                $action->execute($account);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
