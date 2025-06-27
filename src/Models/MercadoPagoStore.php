<?php

namespace BoreiStudio\FilamentMercadoPago\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoStore extends Model
{
    protected $table = 'mercado_pago_stores';

    protected $fillable = [
        'external_id',
        'name',
        'street_name',
        'street_number',
        'city_name',
        'state_name',
        'reference',
        'latitude',
        'longitude',
        'business_hours',
        'active',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'active' => 'boolean',
    ];

    public function getBusinessHoursForApi(): array
{
    if (!is_array($this->business_hours)) {
        return [];
    }

    $grouped = [];

    foreach ($this->business_hours as $item) {
        if (!isset($item['day'], $item['open'], $item['close'])) {
            continue;
        }

        $grouped[$item['day']][] = [
            'open' => $item['open'],
            'close' => $item['close'],
        ];
    }

    return $grouped;
}

}
