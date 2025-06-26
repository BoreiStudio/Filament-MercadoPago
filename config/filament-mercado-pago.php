<?php

return [
    'client_id' => env('MP_CLIENT_ID'),
    'client_secret' => env('MP_CLIENT_SECRET'),
    'redirect_uri' => env('MP_REDIRECT_URI', '/mercado-pago/callback'),
    'model' => \App\Models\User::class, // para relacionar con el usuario
];
