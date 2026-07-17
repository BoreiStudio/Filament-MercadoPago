<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Webhooks\Models;

use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $topic
 * @property string|null $mp_resource_id
 * @property string $raw_payload
 * @property string $status
 * @property bool $signature_valid
 * @property string|null $error
 */
class WebhookEvent extends Model
{
    protected $table = 'mercadopago_webhook_events';

    protected $fillable = [
        'account_id',
        'mp_resource_id',
        'topic',
        'raw_payload',
        'signature_valid',
        'status',
        'error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoAccount::class, 'account_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function hasError(): bool
    {
        return $this->status === 'error';
    }
}
