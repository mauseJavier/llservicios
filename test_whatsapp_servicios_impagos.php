<?php
/**
 * Script de prueba para envío de WhatsApp de servicios impagos
 * 
 * IMPORTANTE: Este es un script de ejemplo. Debes ajustarlo según tu entorno.
 * 
 * Uso:
 * 1. Asegúrate de tener configuradas las variables de WhatsApp en .env
 * 2. Ejecuta: php test_whatsapp_servicios_impagos.php
 */

require __DIR__ . '/vendor/autoload.php';

use App\Services\WhatsAppService;
use App\Jobs\EnviarWhatsAppTodosServiciosImpagosJob;

// Cargar el framework Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "===========================================\n";
echo "  PRUEBA DE NOTIFICACIÓN WHATSAPP\n";
echo "  Servicios Impagos\n";
echo "===========================================\n\n";

// 1. Verificar configuración
echo "1. Verificando configuración de WhatsApp...\n";
$whatsapp = new WhatsAppService();
$config = $whatsapp->validateConfiguration();

if ($config['valid']) {
    echo "   ✓ Configuración válida\n\n";
} else {
    echo "   ✗ Configuración inválida:\n";
    foreach ($config['errors'] as $error) {
        echo "     - $error\n";
    }
    echo "\n";
    exit(1);
}

// 2. Datos de prueba
echo "2. Preparando datos de prueba...\n";

$datosPrueba = [
    'cliente_id' => 1,
    'nombreCliente' => 'Juan Pérez',
    'nombreEmpresa' => 'Mi Empresa Test SRL',
    'cantidad' => 3,
    'servicios' => [
        (object)[
            'nombreServicio' => 'Internet 100MB',
            'cantidad' => 1,
            'precio' => 5000,
            'total' => 5000,
            'fecha' => date('Y-m-d H:i:s')
        ],
        (object)[
            'nombreServicio' => 'Cable HD',
            'cantidad' => 1,
            'precio' => 3000,
            'total' => 3000,
            'fecha' => date('Y-m-d H:i:s')
        ],
        (object)[
            'nombreServicio' => 'Teléfono',
            'cantidad' => 1,
            'precio' => 1500,
            'total' => 1500,
            'fecha' => date('Y-m-d H:i:s')
        ]
    ],
    'total' => 9500
];

echo "   ✓ Datos preparados\n";
echo "   - Cliente: {$datosPrueba['nombreCliente']}\n";
echo "   - Empresa: {$datosPrueba['nombreEmpresa']}\n";
echo "   - Servicios: {$datosPrueba['cantidad']}\n";
echo "   - Total: \${$datosPrueba['total']}\n\n";

// 3. Solicitar número de teléfono
echo "3. Ingrese el número de teléfono para enviar el mensaje de prueba\n";
echo "   (Formato: 5492942506803 o dejar vacío para cancelar): ";
$telefono = trim(fgets(STDIN));

if (empty($telefono)) {
    echo "   ✗ Prueba cancelada\n\n";
    exit(0);
}

echo "   ✓ Número: $telefono\n\n";

// 4. Previsualizar mensaje
echo "4. Previsualizando mensaje...\n";
echo "-------------------------------------------\n";

$mensaje = "🔔 *Notificación de Servicios Pendientes*\n\n";
$mensaje .= "Hola *{$datosPrueba['nombreCliente']}*,\n\n";
$mensaje .= "Le informamos desde *{$datosPrueba['nombreEmpresa']}* que tiene *{$datosPrueba['cantidad']}* servicio(s) pendiente(s) de pago:\n\n";

foreach ($datosPrueba['servicios'] as $index => $servicio) {
    $subtotal = number_format($servicio->total, 2, ',', '.');
    $fecha = date('d/m/Y', strtotime($servicio->fecha));
    
    $mensaje .= "📌 *Servicio " . ($index + 1) . ":*\n";
    $mensaje .= "   • Nombre: {$servicio->nombreServicio}\n";
    $mensaje .= "   • Cantidad: {$servicio->cantidad}\n";
    $mensaje .= "   • Precio unitario: \${$servicio->precio}\n";
    $mensaje .= "   • Subtotal: \${$subtotal}\n";
    $mensaje .= "   • Fecha: {$fecha}\n\n";
}

$total = number_format($datosPrueba['total'], 2, ',', '.');
$mensaje .= "💰 *Total adeudado: \${$total}*\n\n";
$mensaje .= "Por favor, regularice su situación a la brevedad posible.\n\n";
$mensaje .= "Ante cualquier consulta, no dude en contactarnos.\n\n";
$mensaje .= "Gracias por su atención. 🙏";

echo $mensaje . "\n";
echo "-------------------------------------------\n\n";

// 5. Confirmar envío
echo "5. ¿Desea enviar este mensaje? (s/n): ";
$confirmar = trim(fgets(STDIN));

if (strtolower($confirmar) !== 's') {
    echo "   ✗ Envío cancelado\n\n";
    exit(0);
}

// 6. Enviar mensaje
echo "\n6. Enviando mensaje...\n";

try {
    // Opción A: Envío directo (síncrono)
    $resultado = $whatsapp->sendTextMessage($telefono, $mensaje);
    
    if ($resultado['success']) {
        echo "   ✓ Mensaje enviado exitosamente\n";
        echo "   Respuesta: " . json_encode($resultado['data'], JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "   ✗ Error al enviar mensaje\n";
        echo "   Error: {$resultado['message']}\n\n";
    }
    
    // Opción B: Envío mediante Job (asíncrono)
    echo "\n7. ¿Desea probar el envío mediante Job? (s/n): ";
    $probarJob = trim(fgets(STDIN));
    
    if (strtolower($probarJob) === 's') {
        echo "   Despachando Job...\n";
        EnviarWhatsAppTodosServiciosImpagosJob::dispatch($telefono, $datosPrueba);
        echo "   ✓ Job despachado correctamente\n";
        echo "   Nota: Debes tener corriendo 'php artisan queue:work' para procesarlo\n\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Excepción: " . $e->getMessage() . "\n\n";
}

echo "===========================================\n";
echo "  PRUEBA FINALIZADA\n";
echo "===========================================\n";
echo "\nRevisa los logs en: storage/logs/laravel.log\n\n";
