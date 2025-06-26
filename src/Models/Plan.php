<?php

namespace BoreiStudio\FilamentMercadoPago\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'mp_plans'; // podés cambiar el nombre de la tabla

    protected $fillable = [
        'name',             // nombre del plan
        'external_id',      // id del plan en Mercado Pago (para sincronizar)
        'status',           // estado (activo, inactivo, etc)
        'description',      // descripción opcional
        'amount',           // precio (transaction_amount)
        'currency',         // moneda, ej: 'ARS', 'USD'
        'frequency',        // frecuencia (ej: 1)
        'frequency_type',   // tipo de frecuencia ('months', 'days')
        'repetitions',      // cantidad de repeticiones (0 para indefinido)
        'payment_methods',  // JSON con métodos de pago aceptados
        'metadata',         // JSON para guardar datos extra
    ];

    protected $casts = [
        'payment_methods' => 'array',
        'metadata' => 'array',
    ];
}
