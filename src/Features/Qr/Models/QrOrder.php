<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Qr\Models;

use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $mp_order_id
 * @property string $status
 * @property string|null $external_reference
 */
class QrOrder extends Model
{
    protected $table = 'mercadopago_qr_orders';

    protected $fillable = [
        'account_id',
        'pos_id',
        'mp_order_id',
        'external_reference',
        'title',
        'total_amount',
        'items',
        'status',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'items' => 'array',
            'raw_payload' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoAccount::class, 'account_id');
    }

    public function pos(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class, 'pos_id');
    }

    public function isOpened(): bool
    {
        return $this->status === 'opened';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
