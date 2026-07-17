<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Pos\Models;

use BoreiStudio\FilamentMercadoPago\Features\Qr\Models\QrOrder;
use BoreiStudio\FilamentMercadoPago\Features\Stores\Models\Store;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $store_id
 * @property string|null $mp_pos_id
 * @property string $name
 * @property string|null $external_id
 * @property bool $fixed_amount
 * @property string|null $category
 * @property string|null $qr_image_url
 */
class PosTerminal extends Model
{
    protected $table = 'mercadopago_pos';

    protected $fillable = [
        'account_id',
        'store_id',
        'mp_pos_id',
        'name',
        'external_id',
        'fixed_amount',
        'category',
        'qr_image_url',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'fixed_amount' => 'boolean',
            'raw_payload' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoAccount::class, 'account_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function qrOrders(): HasMany
    {
        return $this->hasMany(QrOrder::class, 'pos_id');
    }
}
