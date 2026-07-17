<?php

namespace BoreiStudio\FilamentMercadoPago\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookSimulateCommand extends Command
{
    protected $signature = 'mercadopago:webhook-simulate
                           {--payment= : MP payment ID to simulate}
                           {--url= : Your local/expose/ngrok URL for the webhook endpoint}';

    protected $description = 'Simulate a Mercado Pago webhook notification for testing';

    public function handle(): int
    {
        $paymentId = $this->option('payment');
        $baseUrl = $this->option('url');

        if (! $paymentId) {
            $paymentId = $this->ask('Enter the MP payment ID to simulate');
        }

        if (! $baseUrl) {
            $baseUrl = $this->ask('Enter your tunnel URL (e.g. https://your-tunnel.ngrok.io)');
        }

        $webhookUrl = rtrim($baseUrl, '/').'/mercadopago/webhooks';
        $dataId = $paymentId;
        $xRequestId = (string) Str::uuid();

        $payload = [
            'action' => 'payment.created',
            'api_version' => 'v1',
            'data' => ['id' => $dataId],
            'date_created' => now()->toIso8601String(),
            'live_mode' => false,
            'type' => 'payment',
            'user_id' => 'test_user_id',
        ];

        $secret = config('mercadopago.webhook_secret', '');
        if (empty($secret)) {
            $this->warn('MERCADOPAGO_WEBHOOK_SECRET is not configured. Signature will be empty.');
        }

        $manifest = 'id:'.strtolower($dataId).";request-id:{$xRequestId};ts:".now()->getTimestamp().';';
        $signature = hash_hmac('sha256', $manifest, $secret);
        $ts = now()->getTimestamp();
        $xSignature = "ts={$ts},v1={$signature}";

        $this->info("Simulating webhook for payment {$paymentId}...");
        $this->line("POST {$webhookUrl}");
        $this->line("X-Signature: {$xSignature}");
        $this->line("X-Request-Id: {$xRequestId}");

        $response = Http::withHeaders([
            'X-Signature' => $xSignature,
            'X-Request-Id' => $xRequestId,
        ])->post($webhookUrl, $payload);

        $this->line("Response: {$response->status()}");

        if ($response->successful()) {
            $this->info('Webhook sent successfully.');

            return self::SUCCESS;
        }

        $this->error("Webhook failed: {$response->body()}");

        return self::FAILURE;
    }
}
