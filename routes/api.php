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

// ============================================================
// RUTAS DE MERCADOPAGO
// ============================================================

// Rutas para MercadoPago API Service (Preferencias y Pagos)
Route::prefix('mercadopago-api')->group(function () {
    Route::post('/preference', [App\Http\Controllers\MercadoPago\MercadoPagoApiController::class, 'createPreference']);
    Route::get('/payment/{paymentId}', [App\Http\Controllers\MercadoPago\MercadoPagoApiController::class, 'getPayment']);
    Route::post('/payment', [App\Http\Controllers\MercadoPago\MercadoPagoApiController::class, 'createDirectPayment']);
    Route::get('/payment-methods', [App\Http\Controllers\MercadoPago\MercadoPagoApiController::class, 'getPaymentMethods']);
    Route::get('/validate-credentials', [App\Http\Controllers\MercadoPago\MercadoPagoApiController::class, 'validateCredentials']);
});

// Rutas para MercadoPago QR (Sucursales, Cajas y Órdenes QR)
Route::prefix('mercadopago/qr')->group(function () {
    // Gestión de Sucursales
    Route::get('/stores', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'listStores']);
    Route::post('/stores', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'createStore']);
    Route::get('/stores/{storeId}', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'getStore']);
    Route::put('/stores/{storeId}', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'updateStore']);
    Route::delete('/stores/{storeId}', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'deleteStore']);
    
    // Gestión de Cajas/PDV
    Route::post('/pos', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'createPOS']);
    Route::get('/pos/{posId}', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'getPOS']);
    Route::delete('/pos/{posId}', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'deletePOS']);
    
    // Gestión de Órdenes QR
    Route::post('/pos/{posId}/orders', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'createQROrder']);
    Route::get('/pos/{posId}/orders', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'getQROrder']);
    Route::delete('/pos/{posId}/orders', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'deleteQROrder']);
    
    // Utilidades
    Route::get('/validate-config', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'validateConfig']);
    Route::get('/user-id', [App\Http\Controllers\MercadoPago\MercadoPagoQRController::class, 'getUserId']);
});

// Webhook de MercadoPago para notificaciones de pago QR (sin autenticación)
Route::post('/mercadopago/webhook/qr', [App\Http\Controllers\Api\MercadoPagoWebhookController::class, 'handleQRWebhook'])
    ->name('api.mercadopago.webhook.qr');

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

// Ruta pública para guardar un nuevo cliente y vincularlo a una empresa (sin middleware)
            // Ejemplo de uso del endpoint /cliente/guardar
            // POST /api/cliente/guardar
            // Body JSON:
            // {
            //   "nombre": "Juan Pérez",
            //   "correo": "juan@example.com",
            //   "telefono": "123456789",
            //   "dni": 12345678,
            //   "domicilio": "Calle Falsa 123",
            //   "empresa_id": 1
            // }

            // Respuesta esperada:
            // {
            //   "success": true,
            //   "message": "Cliente creado y vinculado exitosamente",
            //   "data": {
            //     "cliente": {
            //       "id": 1,
            //       "nombre": "Juan Pérez",
            //       "correo": "juan@example.com",
            //       "telefono": "123456789",
            //       "dni": 12345678,
            //       "domicilio": "Calle Falsa 123",
            //       "created_at": "2025-11-02T12:00:00.000000Z",
            //       "updated_at": "2025-11-02T12:00:00.000000Z"
            //     },
            //     "empresas": [
            //       {
            //         "id": 1,
            //         "nombre": "Mi Empresa"
            //       }
            //     ]
            //   }
            // }

Route::post('/cliente/guardar', [App\Http\Controllers\Api\ClienteApiController::class, 'guardarCliente']);
