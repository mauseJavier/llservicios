<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateEmpresaApi
{
    /**
     * Autentica la solicitud mediante el token global de la empresa.
     * El token viaja en el header "Authorization: Bearer <token>".
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticación no proporcionado.',
            ], 401);
        }

        $empresa = Empresa::findByApiToken($token);

        if (! $empresa) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticación inválido.',
            ], 401);
        }

        $request->attributes->set('empresa', $empresa);

        return $next($request);
    }
}
