<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Controllers;

use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Jobs\ProcessPaymentWebhookJob;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Jobs\ProcessPointWebhookJob;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Models\WebhookEvent;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Support\SignatureValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController
{
    public function __invoke(
        Request $request,
        SignatureValidator $signatureValidator,
        ?string $account = null,
    ): Response {
        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id');
        $body = $request->getContent();

        $signatureValid = $xSignature && $xRequestId
            ? $signatureValidator->validate($xSignature, $xRequestId, $body)
            : false;

        $payload = json_decode($body, true);

        $event = WebhookEvent::create([
            'account_id' => $account,
            'mp_resource_id' => $payload['data']['id'] ?? null,
            'topic' => $payload['topic'] ?? $payload['type'] ?? null,
            'raw_payload' => $body,
            'signature_valid' => $signatureValid,
            'status' => 'pending',
        ]);

        if (! $signatureValid) {
            $event->update(['status' => 'error', 'error' => 'Invalid signature']);

            return response('Invalid signature', 401);
        }

        $topic = $event->topic;

        if (in_array($topic, ['payment', 'merchant_order'])) {
            dispatch(new ProcessPaymentWebhookJob($event));
        }

        if ($topic === 'point_integration_wh') {
            dispatch(new ProcessPointWebhookJob($event));
        }

        if ($topic === 'merchant_order') {
            dispatch(new ProcessPaymentWebhookJob($event));
        }

        return response('OK', 200);
    }
}
