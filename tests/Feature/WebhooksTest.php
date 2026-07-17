<?php

use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Jobs\ProcessPaymentWebhookJob;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Models\WebhookEvent;
use BoreiStudio\FilamentMercadoPago\Features\Webhooks\Support\SignatureValidator;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $settings = app(MercadoPagoApplicationSettings::class);
    $settings->webhook_secret = 'test_webhook_secret_123';
    $settings->client_id = 'APP_CLIENT_ID';
    $settings->client_secret = 'APP_CLIENT_SECRET';
    $settings->redirect_uri = 'https://ejemplo.com/callback';
    $settings->sandbox_mode = true;
    $settings->save();
});

it('validates correct signature', function () {
    $body = '{"topic":"payment","data":{"id":12345}}';
    $ts = (string) now()->timestamp;
    $xRequestId = 'req-123';

    $manifest = "id:{$xRequestId};request-body:{$body};ts:{$ts};";
    $hash = hash_hmac('sha256', $manifest, 'test_webhook_secret_123');
    $xSignature = "ts={$ts},v1={$hash}";

    $validator = app(SignatureValidator::class);
    $result = $validator->validate($xSignature, $xRequestId, $body);

    expect($result)->toBeTrue();
});

it('rejects invalid signature', function () {
    $body = '{"topic":"payment","data":{"id":12345}}';
    $validator = app(SignatureValidator::class);

    $result = $validator->validate('ts=123456,v1=invalidsignature', 'req-123', $body);

    expect($result)->toBeFalse();
});

it('rejects signature without ts or v1', function () {
    $body = '{"topic":"payment","data":{"id":12345}}';
    $validator = app(SignatureValidator::class);

    $result = $validator->validate('invalid-format', 'req-123', $body);

    expect($result)->toBeFalse();
});

it('creates webhook event on valid request and dispatches job', function () {
    Queue::fake();

    $body = '{"topic":"payment","data":{"id":12345}}';
    $ts = (string) now()->timestamp;
    $xRequestId = 'req-456';

    $manifest = "id:{$xRequestId};request-body:{$body};ts:{$ts};";
    $hash = hash_hmac('sha256', $manifest, 'test_webhook_secret_123');
    $xSignature = "ts={$ts},v1={$hash}";

    $response = $this->postJson('/mercadopago/webhooks', json_decode($body, true), [
        'x-signature' => $xSignature,
        'x-request-id' => $xRequestId,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('mercadopago_webhook_events', [
        'mp_resource_id' => '12345',
        'topic' => 'payment',
        'signature_valid' => true,
    ]);

    Queue::assertPushed(ProcessPaymentWebhookJob::class);
});

it('returns 401 on invalid signature', function () {
    $body = '{"topic":"payment","data":{"id":12345}}';

    $response = $this->postJson('/mercadopago/webhooks', json_decode($body, true), [
        'x-signature' => 'ts=123,v1=badhash',
        'x-request-id' => 'req-789',
    ]);

    $response->assertStatus(401);

    $this->assertDatabaseHas('mercadopago_webhook_events', [
        'signature_valid' => false,
        'status' => 'error',
    ]);
});

it('processes payment webhook job', function () {
    MercadoPagoAccount::create([
        'mp_user_id' => 123456789,
        'access_token' => 'APP_USR-TEST',
        'public_key' => 'APP_PUB',
        'status' => 'connected',
    ]);

    $event = WebhookEvent::create([
        'mp_resource_id' => '99999',
        'topic' => 'payment',
        'raw_payload' => '{"topic":"payment","data":{"id":99999}}',
        'signature_valid' => true,
        'status' => 'pending',
    ]);

    $job = new ProcessPaymentWebhookJob($event);

    $event->refresh();

    expect($event->status)->toBe('pending');
});
