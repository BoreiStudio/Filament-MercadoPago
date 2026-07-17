<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Support;

use BoreiStudio\FilamentMercadoPago\Settings\MercadoPagoApplicationSettings;

class SignatureValidator
{
    public function __construct(
        private readonly MercadoPagoApplicationSettings $settings,
    ) {}

    public function validate(string $xSignature, string $xRequestId, string $body): bool
    {
        $secret = $this->settings->webhook_secret;

        if (blank($secret)) {
            return false;
        }

        $parts = explode(',', $xSignature);
        $ts = null;
        $hash = null;

        foreach ($parts as $part) {
            $part = trim($part);

            if (str_starts_with($part, 'ts=')) {
                $ts = substr($part, 3);
            } elseif (str_starts_with($part, 'v1=')) {
                $hash = substr($part, 3);
            }
        }

        if (! $ts || ! $hash) {
            return false;
        }

        $manifest = "id:{$xRequestId};request-body:{$body};ts:{$ts};";

        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $hash);
    }
}
