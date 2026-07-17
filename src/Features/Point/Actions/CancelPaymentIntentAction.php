<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Point\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use MercadoPago\Client\Point\PointClient;
use MercadoPago\MercadoPagoConfig;

class CancelPaymentIntentAction
{
    public function __construct(
        private readonly CredentialResolverInterface $credentialResolver,
    ) {}

    public function execute(PointDevice $device, string $paymentIntentId): void
    {
        $credentials = $this->credentialResolver->resolve();

        MercadoPagoConfig::setAccessToken($credentials->getAccessToken());

        $client = new PointClient;

        $client->cancelPaymentIntent($device->device_id, $paymentIntentId);
    }
}
