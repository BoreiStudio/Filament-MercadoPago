<?php

namespace BoreiStudio\FilamentMercadoPago\Policies;

use Illuminate\Database\Eloquent\Model;

class StorePolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, $store): bool
    {
        return true;
    }

    public function create(Model $user): bool
    {
        return true;
    }

    public function update(Model $user, $store): bool
    {
        return true;
    }

    public function delete(Model $user, $store): bool
    {
        return true;
    }
}
