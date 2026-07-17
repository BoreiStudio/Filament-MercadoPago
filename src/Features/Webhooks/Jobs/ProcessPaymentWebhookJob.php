<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Jobs;

use BoreiStudio\FilamentMercadoPago\Features\Payments\Actions\SyncPaymentFromApiAction;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Models\WebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(
        public WebhookEvent $event,
    ) {}

    public function handle(SyncPaymentFromApiAction $syncAction): void
    {
        $mpId = $this->event->mp_resource_id;

        if (! $mpId) {
            $this->event->update(['status' => 'error', 'error' => 'Missing mp_resource_id']);

            return;
        }

        try {
            $syncAction->execute((int) $mpId);

            $this->event->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->event->update([
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
