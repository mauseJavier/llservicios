<?php

/**
 * EJEMPLOS DE USO RÁPIDO - WhatsApp Service
 * 
 * Copia estos ejemplos en tus controladores, jobs o donde necesites enviar WhatsApp
 */

// ============================================================
// EJEMPLO 1: Enviar mensaje simple desde un controlador
// ============================================================

use App\Services\WhatsAppService;

$whatsapp = new WhatsAppService();
$whatsapp->sendTextMessage('5492942506803', 'Hola! Este es un mensaje de prueba');


// ============================================================
// EJEMPLO 2: Enviar notificación de pago registrado
// ============================================================

$cliente = Cliente::find($clienteId);
$pago = Pago::find($pagoId);

$whatsapp = new WhatsAppService();
$mensaje = "✅ Pago Registrado\n\n" .
           "Cliente: {$cliente->nombre}\n" .
           "Monto: \${$pago->monto}\n" .
           "Concepto: {$pago->concepto}\n" .
           "Fecha: {$pago->fecha->format('d/m/Y')}\n\n" .
           "¡Gracias por tu pago!";

$whatsapp->sendTextMessage($cliente->telefono, $mensaje);


// ============================================================
// EJEMPLO 3: Enviar recibo en PDF
// ============================================================

use App\Services\WhatsAppService;
use App\Services\PdfService;

$recibo = Recibo::find($reciboId);
$cliente = $recibo->cliente;

// Generar PDF
$pdfService = new PdfService();
$pdfUrl = $pdfService->generarReciboPDF($recibo);

// Enviar por WhatsApp
$whatsapp = new WhatsAppService();
$whatsapp->sendDocument(
    $cliente->telefono,
    $pdfUrl,
    "Recibo-{$recibo->numero}.pdf",
    "🧾 Tu recibo de pago adjunto"
);


// ============================================================
// EJEMPLO 4: Usar Jobs para envío asíncrono (recomendado)
// ============================================================

use App\Jobs\EnviarWhatsAppJob;

// Enviar texto
EnviarWhatsAppJob::dispatch('5492942506803', 'Mensaje de prueba', 'text');

// Enviar documento
EnviarWhatsAppJob::dispatch(
    '5492942506803',
    'https://ejemplo.com/documento.pdf',
    'document',
    ['filename' => 'documento.pdf', 'caption' => 'Tu documento']
);

// Enviar imagen
EnviarWhatsAppJob::dispatch(
    '5492942506803',
    'https://ejemplo.com/imagen.jpg',
    'image',
    ['caption' => 'Mira esto!']
);


// ============================================================
// EJEMPLO 5: Recordatorio de servicios impagos
// ============================================================

$serviciosImpagos = Servicio::where('estado', 'impago')
    ->where('cliente_id', $clienteId)
    ->get();

if ($serviciosImpagos->count() > 0) {
    $cliente = Cliente::find($clienteId);
    
    $mensaje = "⚠️ Servicios Pendientes de Pago\n\n";
    $mensaje .= "Hola {$cliente->nombre},\n\n";
    $mensaje .= "Tienes {$serviciosImpagos->count()} servicio(s) pendiente(s):\n\n";
    
    foreach ($serviciosImpagos as $servicio) {
        $mensaje .= "• {$servicio->descripcion} - \${$servicio->monto}\n";
    }
    
    $mensaje .= "\nPor favor, regulariza tu situación a la brevedad.";
    
    $whatsapp = new WhatsAppService();
    $whatsapp->sendTextMessage($cliente->telefono, $mensaje);
}


// ============================================================
// EJEMPLO 6: Envío masivo a múltiples clientes
// ============================================================

use App\Jobs\EnviarWhatsAppJob;

$clientes = Cliente::where('activo', true)->get();
$mensaje = "🎉 ¡Tenemos una promoción especial! Consulta nuestras ofertas.";

foreach ($clientes as $cliente) {
    if (!empty($cliente->telefono)) {
        // Usar Job para envío asíncrono
        EnviarWhatsAppJob::dispatch($cliente->telefono, $mensaje, 'text')
            ->delay(now()->addSeconds(5 * $cliente->id)); // Espaciar envíos
    }
}


// ============================================================
// EJEMPLO 7: Notificación con Listener (cuando se registra un pago)
// ============================================================

// En app/Listeners/NotificarPagoWhatsAppListener.php

namespace App\Listeners;

use App\Events\PagoServicioEvent;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotificarPagoWhatsAppListener implements ShouldQueue
{
    public function handle(PagoServicioEvent $event)
    {
        $pago = $event->pago;
        $cliente = $pago->cliente;
        
        $mensaje = "✅ Pago confirmado!\n\n" .
                   "Monto: \${$pago->monto}\n" .
                   "Referencia: {$pago->id}\n\n" .
                   "Gracias por tu pago!";
        
        $whatsapp = new WhatsAppService();
        $whatsapp->sendTextMessage($cliente->telefono, $mensaje);
    }
}


// ============================================================
// EJEMPLO 8: Comando Artisan para enviar mensajes de prueba
// ============================================================

// En app/Console/Commands/TestWhatsAppCommand.php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsAppCommand extends Command
{
    protected $signature = 'whatsapp:test {phone} {message}';
    protected $description = 'Enviar un mensaje de prueba por WhatsApp';

    public function handle()
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');
        
        $whatsapp = new WhatsAppService();
        $resultado = $whatsapp->sendTextMessage($phone, $message);
        
        if ($resultado['success']) {
            $this->info('✓ Mensaje enviado correctamente');
        } else {
            $this->error('✗ Error: ' . $resultado['error']);
        }
    }
}

// Uso: php artisan whatsapp:test 5492942506803 "Hola mundo"


// ============================================================
// EJEMPLO 9: Validar configuración antes de enviar
// ============================================================

$whatsapp = new WhatsAppService();
$validacion = $whatsapp->validateConfiguration();

if (!$validacion['valid']) {
    Log::error('WhatsApp no configurado correctamente', $validacion['errors']);
    return response()->json([
        'error' => 'Servicio de WhatsApp no disponible',
        'details' => $validacion['errors']
    ], 500);
}

// Continuar con el envío...


// ============================================================
// EJEMPLO 10: Enviar con estructura completa personalizada
// ============================================================

$whatsapp = new WhatsAppService();

$payload = [
    'key' => [
        'remoteJid' => '5492942506803@s.whatsapp.net',
        'fromMe' => true,
        'id' => strtoupper(bin2hex(random_bytes(20)))
    ],
    'pushName' => 'Mi Empresa',
    'status' => 'PENDING',
    'message' => [
        'conversation' => 'Mensaje personalizado con estructura completa'
    ],
    'messageType' => 'conversation',
    'messageTimestamp' => time(),
    'instanceId' => config('services.whatsapp.instance_id'),
    'source' => 'laravel_app'
];

$resultado = $whatsapp->sendCustomMessage($payload);


// ============================================================
// EJEMPLO 11: Usar en un API Controller con validación
// ============================================================

public function enviarMensaje(Request $request)
{
    $request->validate([
        'cliente_id' => 'required|exists:clientes,id',
        'mensaje' => 'required|string|max:1000'
    ]);
    
    $cliente = Cliente::findOrFail($request->cliente_id);
    
    if (empty($cliente->telefono)) {
        return response()->json([
            'error' => 'Cliente sin teléfono registrado'
        ], 400);
    }
    
    $whatsapp = new WhatsAppService();
    $resultado = $whatsapp->sendTextMessage(
        $cliente->telefono,
        $request->mensaje
    );
    
    if ($resultado['success']) {
        // Opcional: Registrar en base de datos
        NotificacionWhatsApp::create([
            'cliente_id' => $cliente->id,
            'mensaje' => $request->mensaje,
            'enviado_at' => now()
        ]);
    }
    
    return response()->json($resultado);
}


// ============================================================
// EJEMPLO 12: Envío con try-catch para manejar errores
// ============================================================

try {
    $whatsapp = new WhatsAppService();
    $resultado = $whatsapp->sendTextMessage(
        $cliente->telefono,
        $mensaje
    );
    
    if (!$resultado['success']) {
        throw new \Exception($resultado['error']);
    }
    
    Log::info("WhatsApp enviado a {$cliente->nombre}");
    
} catch (\Exception $e) {
    Log::error("Error enviando WhatsApp: " . $e->getMessage());
    
    // Opcional: Notificar al admin o guardar para reintentar
    ErrorLog::create([
        'tipo' => 'whatsapp',
        'mensaje' => $e->getMessage(),
        'cliente_id' => $cliente->id
    ]);
}
