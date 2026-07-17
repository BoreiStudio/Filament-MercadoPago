<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Point\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Point\Models\PointDevice;
use MercadoPago\Client\Point\PointClient;
use MercadoPago\MercadoPagoConfig;

class CreatePaymentIntentAction
{
    public function __construct(
        private readonly CredentialResolverInterface $credentialResolver,
    ) {}

    public function execute(
        PointDevice $device,
        float $amount,
        ?string $externalReference = null,
    ): array {
        $credentials = $this->credentialResolver->resolve();

        MercadoPagoConfig::setAccessToken($credentials->getAccessToken());

        $client = new PointClient;

        $intent = $client->createPaymentIntent(
            $device->device_id,
            [
                'amount' => $amount,
                'description' => $externalReference ?? 'Cobro Point',
            ]
        );

        return [
            'payment_intent_id' => $intent->id,
            'status' => $intent->status ?? 'pending',
        ];
    }
}
