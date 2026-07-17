<?php

namespace BoreiStudio\FilamentMercadoPago\Support\Http;

use Exception;

class MercadoPagoException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly ?array $mpResponse = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
