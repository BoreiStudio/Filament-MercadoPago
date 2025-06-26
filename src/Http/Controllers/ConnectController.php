<?php

namespace BoreiStudio\FilamentMercadoPago\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use BoreiStudio\FilamentMercadoPago\Models\MercadoPagoAccount;
use BoreiStudio\FilamentMercadoPago\Support\MercadoPagoPKCE;
use Illuminate\Support\Str;

class ConnectController extends Controller
{
    public function redirect()
    {
        $clientId = config('filament-mercado-pago.client_id');
        $redirectUri = route('mercadopago.callback');
        $state = Crypt::encryptString(Auth::id());

        $codeChallenge = MercadoPagoPKCE::generateChallenge();

        $authUrl = sprintf(
            'https://auth.mercadopago.com.ar/authorization?client_id=%s&response_type=code&platform_id=mp&redirect_uri=%s&state=%s&code_challenge=%s&code_challenge_method=S256',
            $clientId,
            urlencode($redirectUri),
            urlencode($state),
            $codeChallenge
        );

        Log::debug('[MercadoPago] Redirigiendo a autorización', compact('clientId', 'redirectUri', 'state', 'codeChallenge', 'authUrl'));

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        Log::debug('[MercadoPago] Callback recibido', [
            'query' => $request->query(),
        ]);

        if (!$request->has(['code', 'state'])) {
            Log::warning('[MercadoPago] Faltan parámetros en el callback');
            return redirect('/admin')->with('error', 'Faltan parámetros de autenticación.');
        }

        try {
            $userId = Crypt::decryptString($request->input('state'));
            Log::debug('[MercadoPago] User ID desencriptado', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('[MercadoPago] Error desencriptando el state', ['exception' => $e]);
            return redirect('/admin')->with('error', 'El estado de la autenticación no es válido.');
        }

        $codeVerifier = MercadoPagoPKCE::getVerifier();

        if (!$codeVerifier) {
            Log::error('[MercadoPago] code_verifier no encontrado en sesión');
            return redirect('/admin')->with('error', 'Error interno. Intenta conectar nuevamente.');
        }

        $response = Http::asForm()->post('https://api.mercadopago.com/oauth/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => config('filament-mercado-pago.client_id'),
            'client_secret' => config('filament-mercado-pago.client_secret'),
            'code'          => $request->input('code'),
            'redirect_uri'  => route('mercadopago.callback'),
            'code_verifier' => $codeVerifier,
        ]);

        session()->forget('mp_code_verifier'); // limpiar por seguridad

        if (!$response->successful()) {
            Log::error('[MercadoPago] Falló el intercambio de token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return redirect('/admin')->with('error', 'Error al obtener token de MercadoPago');
        }

        $data = $response->json();
        Log::debug('[MercadoPago] Token recibido', $data);

        $account = MercadoPagoAccount::updateOrCreate(
            ['user_id' => $userId],
            [
                'access_token'  => Crypt::encryptString($data['access_token']),
                'refresh_token' => $data['refresh_token'],
                'expires_in'    => now()->addSeconds($data['expires_in']),
                'public_key'    => isset($data['public_key']) ? Crypt::encryptString($data['public_key']) : null,
                'scope'         => $data['scope'] ?? null,
                'user_id_mp'    => $data['user_id'] ?? null,
            ]
        );

        Log::info('[MercadoPago] Cuenta guardada correctamente', [
            'user_id' => $userId,
            'account_id' => $account->id,
        ]);

        return redirect('/admin')->with('success', 'Cuenta de Mercado Pago conectada correctamente.');
    }
}
