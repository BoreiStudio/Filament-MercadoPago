<?php

namespace BoreiStudio\FilamentMercadoPago\Support;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class MercadoPagoPKCE
{
    const SESSION_KEY = 'mp_code_verifier';

    /**
     * Genera un code_verifier y code_challenge para OAuth PKCE.
     */
    public static function generateChallenge(): string
    {
        $verifier = Str::random(64);
        Session::put(self::SESSION_KEY, $verifier);

        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Devuelve el code_verifier desde sesión (y opcionalmente lo elimina).
     */
    public static function getVerifier(bool $forget = true): ?string
    {
        $verifier = Session::get(self::SESSION_KEY);

        if ($forget) {
            Session::forget(self::SESSION_KEY);
        }

        return $verifier;
    }
}
