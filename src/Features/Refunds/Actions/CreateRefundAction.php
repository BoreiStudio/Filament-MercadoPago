<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Refunds\Actions;

use BoreiStudio\FilamentMercadoPago\Contracts\CredentialResolverInterface;
use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Features\Refunds\Models\Refund;
use BoreiStudio\FilamentMercadoPago\Support\Http\MercadoPagoClient;

class CreateRefundAction
{
    public function __construct(
        private readonly CredentialResolverInterface $credentialResolver,
        private readonly ?MercadoPagoClient $client = null,
    ) {}

    public function execute(Payment $payment, ?float $amount = null): Refund
    {
        if ($payment->status !== 'approved') {
            throw new \RuntimeException('Solo se pueden reembolsar pagos aprobados.');
        }

        $totalRefunded = (float) Refund::where('payment_id', $payment->id)->sum('amount');
        $available = (float) $payment->transaction_amount - $totalRefunded;

        if ($available <= 0) {
            throw new \RuntimeException('El pago ya fue reembolsado completamente.');
        }

        if ($amount !== null && $amount > $available) {
            throw new \RuntimeException(
                "El monto máximo a reembolsar es \${$available}. Monto solicitado: \${$amount}."
            );
        }

        $credentials = $this->credentialResolver->resolve();
        $client = $this->client ?? new MercadoPagoClient($credentials);

        $mpPaymentId = $payment->mp_payment_id;

        if ($mpPaymentId === null) {
            throw new \RuntimeException('El pago no tiene un ID de Mercado Pago asociado.');
        }

        $mpRefund = $client->createRefund((int) $mpPaymentId, $amount);

        $refundedAmount = $amount ?? (float) $payment->transaction_amount;

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'account_id' => $payment->account_id,
            'mp_refund_id' => $mpRefund['id'] ?? null,
            'amount' => $refundedAmount,
            'status' => 'approved',
            'raw_payload' => $mpRefund,
        ]);

        $newTotalRefunded = $totalRefunded + $refundedAmount;
        $newStatus = ($newTotalRefunded >= (float) $payment->transaction_amount)
            ? 'refunded'
            : 'partially_refunded';

        $payment->update(['status' => $newStatus]);

        return $refund;
    }
}
