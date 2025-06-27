<?php

namespace BoreiStudio\FilamentMercadoPago\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoTerminal extends Model
{
    protected $fillable = [
        'terminal_id',
        'pos_id',
        'store_id',
        'external_pos_id',
        'operating_mode',
    ];

    protected $casts = [
        'pos_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function store()
    {
        return $this->belongsTo(MercadoPagoStore::class, 'store_id', 'external_id');
    }
}
