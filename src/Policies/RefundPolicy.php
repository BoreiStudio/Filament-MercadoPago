<?php

namespace BoreiStudio\FilamentMercadoPago\Policies;

use Illuminate\Database\Eloquent\Model;

class RefundPolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, $refund): bool
    {
        return true;
    }

    public function create(Model $user): bool
    {
        return true;
    }

    public function update(Model $user, $refund): bool
    {
        return false;
    }

    public function delete(Model $user, $refund): bool
    {
        return false;
    }
}
