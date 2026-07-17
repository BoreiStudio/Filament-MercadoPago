<?php

namespace BoreiStudio\FilamentMercadoPago\Policies;

use Illuminate\Database\Eloquent\Model;

class QrOrderPolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, $order): bool
    {
        return true;
    }

    public function create(Model $user): bool
    {
        return true;
    }

    public function update(Model $user, $order): bool
    {
        return false;
    }

    public function delete(Model $user, $order): bool
    {
        return true;
    }
}
