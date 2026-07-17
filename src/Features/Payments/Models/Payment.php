<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Payments\Models;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $status
 * @property float $transaction_amount
 * @property string|null $mp_payment_id
 * @property string|null $preference_id
 * @property string|null $currency_id
 * @property string|null $payment_type_id
 * @property string|null $payment_method_id
 * @property string|null $payer_email
 * @property string|null $external_reference
 * @property string|null $source
 * @property int $account_id
 */
class Payment extends Model
{
    protected $table = 'mercadopago_payments';

    protected $fillable = [
        'account_id',
        'mp_payment_id',
        'preference_id',
        'status',
        'status_detail',
        'transaction_amount',
        'currency_id',
        'payment_type_id',
        'payment_method_id',
        'payer_email',
        'external_reference',
        'source',
        'raw_payload',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_amount' => 'decimal:2',
            'raw_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoAccount::class, 'account_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->status === 'partially_refunded';
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'pending', 'in_process' => 'warning',
            'rejected', 'cancelled' => 'danger',
            'refunded', 'partially_refunded' => 'gray',
            default => 'gray',
        };
    }
}
