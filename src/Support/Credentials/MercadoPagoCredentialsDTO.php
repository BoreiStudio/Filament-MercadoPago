<?php

namespace BoreiStudio\FilamentMercadoPago\Support\Credentials;

use BoreiStudio\FilamentMercadoPago\Contracts\MercadoPagoCredentials;

class MercadoPagoCredentialsDTO implements MercadoPagoCredentials
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $publicKey,
        private readonly int $mpUserId,
        private readonly bool $liveMode,
    ) {}

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getMpUserId(): int
    {
        return $this->mpUserId;
    }

    public function isLiveMode(): bool
    {
        return $this->liveMode;
    }
}
