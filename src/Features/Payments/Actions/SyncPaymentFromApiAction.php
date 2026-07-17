<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Payments\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Support\Http\MercadoPagoClient;

class SyncPaymentFromApiAction
{
    public function __construct(
        private readonly CredentialResolverInterface $credentialResolver,
        private readonly ?MercadoPagoClient $client = null,
    ) {}

    public function execute(int $mpPaymentId, ?int $accountId = null): Payment
    {
        $credentials = $this->credentialResolver->resolve();

        $client = $this->client ?? new MercadoPagoClient($credentials);

        $mpPayment = $client->getPayment($mpPaymentId);

        if (! $accountId) {
            $account = MercadoPagoAccount::query()
                ->where('mp_user_id', $credentials->getMpUserId())
                ->first();
            $accountId = $account?->id;
        }

        $payment = Payment::updateOrCreate(
            ['mp_payment_id' => $mpPaymentId],
            ['account_id' => $accountId,
                'status' => $mpPayment->status,
                'status_detail' => $mpPayment->status_detail ?? null,
                'transaction_amount' => $mpPayment->transaction_amount ?? 0,
                'currency_id' => $mpPayment->currency_id ?? 'ARS',
                'payment_type_id' => $mpPayment->payment_type_id ?? null,
                'payment_method_id' => $mpPayment->payment_method_id ?? null,
                'payer_email' => $mpPayment->payer?->email ?? null,
                'external_reference' => $mpPayment->external_reference ?? null,
                'paid_at' => $mpPayment->date_approved ?? $mpPayment->date_last_updated ?? now(),
                'raw_payload' => json_decode(json_encode($mpPayment), true),
            ]
        );

        return $payment;
    }
}
