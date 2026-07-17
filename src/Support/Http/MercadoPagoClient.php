<?php

namespace BoreiStudio\FilamentMercadoPago\Support\Http;

use BoreiStudio\FilamentMercadoPago\Contracts\MercadoPagoCredentials;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Payment\PaymentRefundClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;
use MercadoPago\Resources\Preference;

class MercadoPagoClient
{
    public function __construct(
        private readonly MercadoPagoCredentials $credentials,
    ) {
        MercadoPagoConfig::setAccessToken($credentials->getAccessToken());
    }

    public function getPayment(int $mpPaymentId): Payment
    {
        $client = new PaymentClient;

        return $client->get($mpPaymentId);
    }

    public function createPreference(array $data): Preference
    {
        $client = new PreferenceClient;

        $preference = $client->create($data);

        if (! $preference->id) {
            throw new MercadoPagoException('Failed to create Mercado Pago preference.');
        }

        return $preference;
    }

    public function createRefund(int $mpPaymentId, ?float $amount = null): array
    {
        $client = new PaymentRefundClient;

        if ($amount !== null) {
            $refund = $client->refund($mpPaymentId, $amount);
        } else {
            $refund = $client->refundTotal($mpPaymentId);
        }

        return (array) $refund;
    }
}
