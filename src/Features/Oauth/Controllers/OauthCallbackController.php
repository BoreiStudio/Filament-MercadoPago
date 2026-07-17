<?php

namespace BoreiStudio\FilamentMercadoPago\Features\Oauth\Controllers;

use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\ExchangeCodeForTokenAction;
use BoreiStudio\FilamentMercadoPago\Features\Oauth\Actions\GenerateAuthorizationUrlAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OauthCallbackController
{
    public function __invoke(
        Request $request,
        ExchangeCodeForTokenAction $exchangeAction,
        GenerateAuthorizationUrlAction $urlAction,
    ): RedirectResponse {
        $error = $request->query('error');

        if ($error) {
            $description = $request->query('error_description', __('filament-mercadopago::messages.oauth.callback_missing_params'));

            return redirect()->route('filament.admin.mercado-pago.pages.connect-mercado-pago-page')
                ->with('error', __('filament-mercadopago::messages.oauth.callback_error', ['error' => $description]));
        }

        $code = $request->query('code');
        $state = $request->query('state');

        if (! $code || ! $state) {

            return redirect()->route('filament.admin.mercado-pago.pages.connect-mercado-pago-page')
                ->with('error', __('filament-mercadopago::messages.oauth.callback_missing_params'));
        }

        $stateData = $urlAction->validateState($state);

        if (! $stateData) {

            return redirect()->route('filament.admin.mercado-pago.pages.connect-mercado-pago-page')
                ->with('error', __('filament-mercadopago::messages.oauth.callback_invalid_state'));
        }

        $codeVerifier = $stateData['code_verifier'] ?? null;

        if (! $codeVerifier) {

            return redirect()->route('filament.admin.mercado-pago.pages.connect-mercado-pago-page')
                ->with('error', __('filament-mercadopago::messages.oauth.callback_invalid_state'));
        }

        try {
            $exchangeAction->execute(
                code: $code,
                codeVerifier: $codeVerifier,
                tenantId: $stateData['tenant_id'],
                tenantType: $stateData['tenant_type'],
            );

            return redirect()->route('filament.admin.mercado-pago.pages.connect-mercado-pago-page')
                ->with('success', __('filament-mercadopago::messages.oauth.callback_success'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('filament.admin.mercado-pago.pages.connect-mercado-pago-page')
                ->with('error', __('filament-mercadopago::messages.oauth.callback_error', ['error' => $e->getMessage()]));
        }
    }
}
