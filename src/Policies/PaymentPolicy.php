<?php

namespace BoreiStudio\FilamentMercadoPago\Policies;

use Illuminate\Database\Eloquent\Model;

class PaymentPolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, $payment): bool
    {
        return true;
    }

    public function create(Model $user): bool
    {
        return false;
    }

    public function update(Model $user, $payment): bool
    {
        return false;
    }

    public function delete(Model $user, $payment): bool
    {
        return false;
    }
}
