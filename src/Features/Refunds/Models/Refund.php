<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Refunds\Models;

use BoreiStudio\FilamentMercadoPago\Features\Payments\Models\Payment;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $table = 'mercadopago_refunds';

    protected $fillable = [
        'payment_id',
        'account_id',
        'mp_refund_id',
        'amount',
        'status',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoAccount::class, 'account_id');
    }
}
