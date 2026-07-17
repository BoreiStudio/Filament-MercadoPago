<?php

namespace BoreiStudio\FilamentMercadoPago\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MercadoPagoAccount extends Model
{
    protected $table = 'mercadopago_accounts';

    protected $fillable = [
        'tenant_id',
        'tenant_type',
        'mp_user_id',
        'access_token',
        'refresh_token',
        'public_key',
        'scope',
        'expires_at',
        'live_mode',
        'status',
        'last_refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'live_mode' => 'boolean',
            'expires_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
        ];
    }

    public function scopeByTenant(Builder $query, ?Model $tenant = null): Builder
    {
        if ($tenant === null) {
            return $query->whereNull('tenant_id')->whereNull('tenant_type');
        }

        return $query->where('tenant_id', $tenant->getKey())
            ->where('tenant_type', $tenant->getMorphClass());
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
