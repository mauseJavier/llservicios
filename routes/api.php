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

// Rutas para MercadoPago API Service
Route::prefix('mercadopago-api')->group(function () {
    Route::post('/preference', [App\Http\Controllers\MercadoPagoApiController::class, 'createPreference']);
    Route::get('/payment/{paymentId}', [App\Http\Controllers\MercadoPagoApiController::class, 'getPayment']);
    Route::post('/payment', [App\Http\Controllers\MercadoPagoApiController::class, 'createDirectPayment']);
    Route::get('/payment-methods', [App\Http\Controllers\MercadoPagoApiController::class, 'getPaymentMethods']);
    Route::get('/validate-credentials', [App\Http\Controllers\MercadoPagoApiController::class, 'validateCredentials']);
});
