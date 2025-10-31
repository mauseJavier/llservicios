<?php

/**
 * SCRIPT DE PRUEBA RÁPIDA - WhatsApp Service
 * 
 * Ejecuta este script desde artisan tinker para probar el servicio
 * 
 * Uso:
 * php artisan tinker
 * require 'WHATSAPP_TEST_SCRIPT.php';
 * 
 * O copia y pega las funciones en tinker
 */

// ============================================================
// FUNCIÓN 1: Validar Configuración
// ============================================================

function testWhatsAppConfig()
{
    echo "🔍 Validando configuración de WhatsApp...\n\n";
    
    $whatsapp = new App\Services\WhatsAppService();
    $validacion = $whatsapp->validateConfiguration();
    
    if ($validacion['valid']) {
        echo "✅ Configuración VÁLIDA\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "API URL: " . ($validacion['config']['api_url'] ? '✓ Configurada' : '✗ No configurada') . "\n";
        echo "Instance ID: " . ($validacion['config']['instance_id'] ? '✓ Configurado' : '✗ No configurado') . "\n";
        echo "API Key: " . ($validacion['config']['api_key_set'] ? '✓ Configurada' : '✗ No configurada') . "\n";
        echo "\n✓ Todo listo para enviar mensajes!\n";
    } else {
        echo "❌ Configuración INVÁLIDA\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Errores encontrados:\n";
        foreach ($validacion['errors'] as $error) {
            echo "  • $error\n";
        }
        echo "\n⚠️  Por favor, configura las variables en tu archivo .env\n";
        echo "   WHATSAPP_API_URL=...\n";
        echo "   WHATSAPP_INSTANCE_ID=...\n";
    }
    
    return $validacion;
}


// ============================================================
// FUNCIÓN 2: Enviar Mensaje de Prueba
// ============================================================

function testSendWhatsApp($phone = '5492942506803', $mensaje = 'Hola! Este es un mensaje de prueba desde Laravel 🚀')
{
    echo "📱 Enviando mensaje de prueba...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Teléfono: $phone\n";
    echo "Mensaje: $mensaje\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $whatsapp = new App\Services\WhatsAppService();
    $resultado = $whatsapp->sendTextMessage($phone, $mensaje);
    
    if ($resultado['success']) {
        echo "✅ MENSAJE ENVIADO EXITOSAMENTE!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Respuesta de la API:\n";
        print_r($resultado['data']);
    } else {
        echo "❌ ERROR AL ENVIAR MENSAJE\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Error: " . $resultado['error'] . "\n";
    }
    
    return $resultado;
}


// ============================================================
// FUNCIÓN 3: Test Completo
// ============================================================

function testWhatsAppComplete($phone = '5492942506803')
{
    echo "\n";
    echo "╔════════════════════════════════════════════════╗\n";
    echo "║   TEST COMPLETO - WhatsApp Service             ║\n";
    echo "╚════════════════════════════════════════════════╝\n";
    echo "\n";
    
    // 1. Validar configuración
    echo "PASO 1: Validar Configuración\n";
    echo "═══════════════════════════════\n";
    $config = testWhatsAppConfig();
    echo "\n\n";
    
    if (!$config['valid']) {
        echo "⚠️  No se puede continuar sin configuración válida.\n";
        return false;
    }
    
    // 2. Enviar mensaje de texto
    echo "PASO 2: Enviar Mensaje de Texto\n";
    echo "═══════════════════════════════\n";
    sleep(1);
    $texto = testSendWhatsApp($phone, "🧪 Test 1: Mensaje de texto simple");
    echo "\n\n";
    
    sleep(2);
    
    // 3. Enviar mensaje con formato
    echo "PASO 3: Enviar Mensaje Formateado\n";
    echo "═══════════════════════════════\n";
    $mensajeFormateado = "📋 *Test WhatsApp Service*\n\n" .
                         "✅ Servicio funcionando correctamente\n" .
                         "📅 Fecha: " . date('d/m/Y H:i') . "\n" .
                         "🚀 Enviado desde Laravel\n\n" .
                         "_Mensaje generado automáticamente_";
    
    $formato = testSendWhatsApp($phone, $mensajeFormateado);
    echo "\n\n";
    
    // Resumen
    echo "╔════════════════════════════════════════════════╗\n";
    echo "║   RESUMEN DE PRUEBAS                           ║\n";
    echo "╚════════════════════════════════════════════════╝\n";
    echo "\n";
    echo ($config['valid'] ? "✅" : "❌") . " Configuración: " . ($config['valid'] ? "OK" : "FALLA") . "\n";
    echo ($texto['success'] ? "✅" : "❌") . " Mensaje simple: " . ($texto['success'] ? "OK" : "FALLA") . "\n";
    echo ($formato['success'] ? "✅" : "❌") . " Mensaje formateado: " . ($formato['success'] ? "OK" : "FALLA") . "\n";
    echo "\n";
    
    $exitosos = ($config['valid'] ? 1 : 0) + ($texto['success'] ? 1 : 0) + ($formato['success'] ? 1 : 0);
    echo "📊 Total: $exitosos/3 pruebas exitosas\n\n";
    
    if ($exitosos === 3) {
        echo "🎉 ¡Todas las pruebas pasaron correctamente!\n";
        echo "   Tu servicio de WhatsApp está listo para usar.\n";
    } else {
        echo "⚠️  Algunas pruebas fallaron. Revisa los errores arriba.\n";
    }
    
    return $exitosos === 3;
}


// ============================================================
// FUNCIÓN 4: Test de Documento (requiere URL válida)
// ============================================================

function testWhatsAppDocument($phone = '5492942506803', $documentUrl = null, $filename = 'documento-test.pdf')
{
    if (!$documentUrl) {
        echo "⚠️  Por favor proporciona una URL de documento válida.\n";
        echo "   Uso: testWhatsAppDocument('5492942506803', 'https://ejemplo.com/doc.pdf')\n";
        return false;
    }
    
    echo "📄 Enviando documento...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Teléfono: $phone\n";
    echo "Documento: $documentUrl\n";
    echo "Nombre: $filename\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $whatsapp = new App\Services\WhatsAppService();
    $resultado = $whatsapp->sendDocument(
        $phone,
        $documentUrl,
        $filename,
        "📎 Documento de prueba adjunto"
    );
    
    if ($resultado['success']) {
        echo "✅ DOCUMENTO ENVIADO!\n";
    } else {
        echo "❌ ERROR: " . $resultado['error'] . "\n";
    }
    
    return $resultado;
}


// ============================================================
// FUNCIÓN 5: Test de Imagen (requiere URL válida)
// ============================================================

function testWhatsAppImage($phone = '5492942506803', $imageUrl = null)
{
    if (!$imageUrl) {
        echo "⚠️  Por favor proporciona una URL de imagen válida.\n";
        echo "   Uso: testWhatsAppImage('5492942506803', 'https://ejemplo.com/imagen.jpg')\n";
        return false;
    }
    
    echo "🖼️  Enviando imagen...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Teléfono: $phone\n";
    echo "Imagen: $imageUrl\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    $whatsapp = new App\Services\WhatsAppService();
    $resultado = $whatsapp->sendImage(
        $phone,
        $imageUrl,
        "🖼️ Imagen de prueba"
    );
    
    if ($resultado['success']) {
        echo "✅ IMAGEN ENVIADA!\n";
    } else {
        echo "❌ ERROR: " . $resultado['error'] . "\n";
    }
    
    return $resultado;
}


// ============================================================
// FUNCIÓN 6: Test de Job Asíncrono
// ============================================================

function testWhatsAppJob($phone = '5492942506803')
{
    echo "🔄 Despachando Job asíncrono...\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    App\Jobs\EnviarWhatsAppJob::dispatch(
        $phone,
        "🔄 Este mensaje fue enviado de forma asíncrona usando un Job",
        'text'
    );
    
    echo "✅ Job despachado correctamente!\n";
    echo "   El mensaje se enviará en segundo plano.\n";
    echo "   Verifica los logs: tail -f storage/logs/laravel.log\n";
    echo "\n⚠️  Asegúrate de que el queue worker esté corriendo:\n";
    echo "   php artisan queue:work\n";
}


// ============================================================
// MOSTRAR AYUDA
// ============================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║   📱 WhatsApp Service - Scripts de Prueba            ║\n";
echo "╚══════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Funciones disponibles:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
echo "1️⃣  testWhatsAppConfig()\n";
echo "   Valida que el servicio esté configurado correctamente\n";
echo "\n";
echo "2️⃣  testSendWhatsApp(\$phone, \$mensaje)\n";
echo "   Envía un mensaje de texto simple\n";
echo "   Ejemplo: testSendWhatsApp('5492942506803', 'Hola!')\n";
echo "\n";
echo "3️⃣  testWhatsAppComplete(\$phone)\n";
echo "   Ejecuta todas las pruebas básicas\n";
echo "   Ejemplo: testWhatsAppComplete('5492942506803')\n";
echo "\n";
echo "4️⃣  testWhatsAppDocument(\$phone, \$documentUrl, \$filename)\n";
echo "   Envía un documento PDF\n";
echo "   Ejemplo: testWhatsAppDocument('5492942506803', 'https://...pdf')\n";
echo "\n";
echo "5️⃣  testWhatsAppImage(\$phone, \$imageUrl)\n";
echo "   Envía una imagen\n";
echo "   Ejemplo: testWhatsAppImage('5492942506803', 'https://...jpg')\n";
echo "\n";
echo "6️⃣  testWhatsAppJob(\$phone)\n";
echo "   Despacha un Job asíncrono\n";
echo "   Ejemplo: testWhatsAppJob('5492942506803')\n";
echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";
echo "🚀 INICIO RÁPIDO:\n";
echo "   testWhatsAppComplete('TU_NUMERO')\n";
echo "\n";
echo "📝 NOTA: Reemplaza 'TU_NUMERO' con tu número de WhatsApp\n";
echo "         Formato: 5492942506803 (código país + número)\n";
echo "\n";
