<?php

namespace BoreiStudio\FilamentMercadoPago\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoStore extends Model
{
    protected $table = 'mercado_pago_stores';

    protected $fillable = [
        'user_id',
        'external_id',
        'name',
        'location',
        'active',
    ];
}
