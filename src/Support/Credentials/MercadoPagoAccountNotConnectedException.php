<?php

namespace BoreiStudio\FilamentMercadoPago\Support\Credentials;

use Exception;

class MercadoPagoAccountNotConnectedException extends Exception
{
    public function __construct(string $message = '')
    {
        parent::__construct($message);
    }
}
