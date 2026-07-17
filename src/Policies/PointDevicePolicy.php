<?php

namespace BoreiStudio\FilamentMercadoPago\Policies;

use Illuminate\Database\Eloquent\Model;

class PointDevicePolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, $device): bool
    {
        return true;
    }

    public function create(Model $user): bool
    {
        return false;
    }

    public function update(Model $user, $device): bool
    {
        return false;
    }

    public function delete(Model $user, $device): bool
    {
        return false;
    }
}
