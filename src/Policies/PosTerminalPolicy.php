<?php

namespace BoreiStudio\FilamentMercadoPago\Policies;

use Illuminate\Database\Eloquent\Model;

class PosTerminalPolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, $pos): bool
    {
        return true;
    }

    public function create(Model $user): bool
    {
        return true;
    }

    public function update(Model $user, $pos): bool
    {
        return true;
    }

    public function delete(Model $user, $pos): bool
    {
        return true;
    }
}
