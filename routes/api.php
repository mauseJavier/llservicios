<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ============================================================
// DOCUMENTACIÓN DE LA API
// ============================================================
Route::prefix('docs')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ApiDocumentationController::class, 'index'])
        ->name('api.docs.index');
    Route::get('/groups', [App\Http\Controllers\Api\ApiDocumentationController::class, 'groups'])
        ->name('api.docs.groups');
    Route::get('/{group}', [App\Http\Controllers\Api\ApiDocumentationController::class, 'show'])
        ->name('api.docs.show');
});

// ============================================================
// RUTAS DE LA API
// ============================================================

// Rutas para MercadoPago API Service
Route::prefix('mercadopago-api')->group(function () {
    Route::post('/preference', [App\Http\Controllers\MercadoPagoApiController::class, 'createPreference']);
    Route::get('/payment/{paymentId}', [App\Http\Controllers\MercadoPagoApiController::class, 'getPayment']);
    Route::post('/payment', [App\Http\Controllers\MercadoPagoApiController::class, 'createDirectPayment']);
    Route::get('/payment-methods', [App\Http\Controllers\MercadoPagoApiController::class, 'getPaymentMethods']);
    Route::get('/validate-credentials', [App\Http\Controllers\MercadoPagoApiController::class, 'validateCredentials']);
});

// Ruta pública para buscar cliente (sin middleware)
            // Ejemplo de uso del endpoint /cliente/buscar
            // GET /api/cliente/buscar?dni=12345678&correo=cliente@email.com&nombre=Juan&empresa_id=1&nombre_empresa=MiEmpresa

            // Respuesta esperada:
            // {
            //   "success": true,
            //   "data": {
            //     "id": 1,
            //     "dni": "12345678",
            //     "nombre": "Juan Pérez",
            //     "correo": "cliente@email.com",
            //     "empresa_id": 1,
            //     "empresa": {
            //       "id": 1,
            //       "nombre": "MiEmpresa"
            //     }
            //   }
            // }

Route::get('/cliente/buscar', [App\Http\Controllers\Api\ClienteApiController::class, 'buscarCliente']);
