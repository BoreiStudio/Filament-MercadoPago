<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Jobs;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Models\WebhookEvent;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use MercadoPago\Client\Point\PointClient;
use MercadoPago\MercadoPagoConfig;

class ProcessPointWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(
        public WebhookEvent $event,
    ) {}

    public function handle(CredentialResolverInterface $credentialResolver): void
    {
        $payload = json_decode($this->event->raw_payload, true);
        $paymentIntentId = $this->event->mp_resource_id ?? $payload['data']['id'] ?? null;

        if (! $paymentIntentId) {
            $this->event->update(['status' => 'error', 'error' => 'Missing payment_intent_id']);

            return;
        }

        try {
            $credentials = $credentialResolver->resolve();

            MercadoPagoConfig::setAccessToken($credentials->getAccessToken());

            $client = new PointClient;
            $status = $client->getPaymentIntentStatus($paymentIntentId);

            $account = MercadoPagoAccount::query()
                ->where('mp_user_id', $credentials->getMpUserId())
                ->first();

            if ($status->status === 'approved') {
                $device = PointDevice::query()
                    ->where('device_id', $status->device_id ?? null)
                    ->first();

                Payment::create([
                    'account_id' => $account?->id,
                    'mp_payment_id' => $status->payment_id ?? null,
                    'status' => 'approved',
                    'transaction_amount' => $status->amount ?? 0,
                    'external_reference' => $status->description ?? null,
                    'source' => 'point',
                    'paid_at' => now(),
                    'raw_payload' => json_decode(json_encode($status), true),
                ]);
            }

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
