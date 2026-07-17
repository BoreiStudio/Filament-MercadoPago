<?php

namespace BoreiStudio\FilamentMercadoPago\Policies;

use Illuminate\Database\Eloquent\Model;

class WebhookEventPolicy
{
    public function viewAny(Model $user): bool
    {
        return true;
    }

    public function view(Model $user, $event): bool
    {
        return true;
    }

    public function create(Model $user): bool
    {
        return false;
    }

    public function update(Model $user, $event): bool
    {
        return false;
    }

    public function delete(Model $user, $event): bool
    {
        return false;
    }
}
