<?php

namespace BoreiStudio\FilamentMercadoPago\Contracts;

interface MercadoPagoCredentials
{
    public function getAccessToken(): string;

    public function getPublicKey(): string;

    public function getMpUserId(): int;

    public function isLiveMode(): bool;
}
