<?php

namespace BoreiStudio\FilamentMercadoPago\Contracts;

use BoreiStudio\FilamentMercadoPago\Support\Credentials\MercadoPagoCredentialsDTO;

interface CredentialResolverInterface
{
    public function resolve(): MercadoPagoCredentialsDTO;

    public function applicationCredentials(): array;
}
