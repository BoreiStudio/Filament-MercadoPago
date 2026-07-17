<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Stores\Models;

use BoreiStudio\FilamentMercadoPago\Features\Pos\Models\PosTerminal;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $table = 'mercadopago_stores';

    protected $fillable = [
        'account_id',
        'mp_store_id',
        'name',
        'external_id',
        'business_hours',
        'location',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'business_hours' => 'array',
            'location' => 'array',
            'raw_payload' => 'array',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MercadoPagoAccount::class, 'account_id');
    }

    public function posTerminals(): HasMany
    {
        return $this->hasMany(PosTerminal::class, 'store_id');
    }
}
