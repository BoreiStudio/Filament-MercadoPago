<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Point\Models;

use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $device_id
 * @property string|null $operating_mode
 * @property string|null $status
 */
class PointDevice extends Model
{
    protected $table = 'mercadopago_point_devices';

    protected $fillable = [
        'account_id',
        'pos_id',
        'device_id',
        'model',
        'operating_mode',
        'status',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
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
}
