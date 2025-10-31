<?php

/**
 * Rutas de WhatsApp API
 * 
 * Agregar estas rutas al archivo routes/api.php
 */

use App\Http\Controllers\WhatsAppController;

// Grupo de rutas para WhatsApp
Route::prefix('whatsapp')->group(function () {
    
    // Enviar mensaje de texto
    Route::post('/send-text', [WhatsAppController::class, 'sendText']);
    
    // Enviar documento
    Route::post('/send-document', [WhatsAppController::class, 'sendDocument']);
    
    // Enviar imagen
    Route::post('/send-image', [WhatsAppController::class, 'sendImage']);
    
    // Enviar mensaje personalizado
    Route::post('/send-custom', [WhatsAppController::class, 'sendCustom']);
    
    // Validar configuración
    Route::get('/validate-config', [WhatsAppController::class, 'validateConfig']);
    
    // Notificar cliente (ejemplo de uso)
    Route::post('/notificar-cliente', [WhatsAppController::class, 'notificarCliente']);
});

// Ejemplo con middleware de autenticación
Route::middleware(['auth:sanctum'])->prefix('whatsapp')->group(function () {
    Route::post('/send-text', [WhatsAppController::class, 'sendText']);
    Route::post('/send-document', [WhatsAppController::class, 'sendDocument']);
    Route::post('/send-image', [WhatsAppController::class, 'sendImage']);
});
