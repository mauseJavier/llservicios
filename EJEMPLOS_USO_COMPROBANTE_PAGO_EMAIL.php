<?php

/**
 * EJEMPLOS DE USO: EnviarComprobantePagoEmailJob
 * 
 * Este Job envía un correo electrónico al cliente con un comprobante de pago
 * en formato PDF adjunto. El Job obtiene automáticamente los datos del servicio
 * pagado desde la base de datos.
 */

use App\Jobs\EnviarComprobantePagoEmailJob;

// ============================================================================
// EJEMPLO 1: Uso básico - Solo con el ID del servicio pagado
// ============================================================================
// El Job obtiene automáticamente todos los datos necesarios de la base de datos

$idServicioPagar = 123; // ID del registro en la tabla servicio_pagar

// Despachar el Job de forma síncrona (se ejecuta inmediatamente)
EnviarComprobantePagoEmailJob::dispatch($idServicioPagar);


// ============================================================================
// EJEMPLO 2: Despachar el Job a una cola (recomendado para producción)
// ============================================================================
// Esto permite que el Job se ejecute en segundo plano sin bloquear la petición

$idServicioPagar = 123;

// Encolar el Job para ejecución asíncrona
EnviarComprobantePagoEmailJob::dispatch($idServicioPagar)
    ->onQueue('emails'); // Opcional: especificar la cola


// ============================================================================
// EJEMPLO 3: Con datos adicionales personalizados
// ============================================================================
// Puedes proporcionar datos adicionales que sobrescribirán o complementarán
// los datos obtenidos de la base de datos

$idServicioPagar = 123;

$datosAdicionales = [
    'forma_pago' => 'Efectivo',
    'importe' => 1500.00,
    'comentario' => 'Pago recibido en efectivo - Gracias por su preferencia',
    'fechaPago' => '15/12/2024 10:30',
];

EnviarComprobantePagoEmailJob::dispatch($idServicioPagar, $datosAdicionales);


// ============================================================================
// EJEMPLO 4: Uso en un controlador después de registrar un pago
// ============================================================================

namespace App\Http\Controllers;

use App\Jobs\EnviarComprobantePagoEmailJob;
use App\Models\ServicioPagar;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function registrarPago(Request $request)
    {
        // Validar los datos del pago
        $validated = $request->validate([
            'servicio_pagar_id' => 'required|exists:servicio_pagar,id',
            'forma_pago' => 'required|string',
            'importe' => 'required|numeric|min:0',
        ]);

        // Actualizar el estado del servicio a "pagado"
        $servicioPagar = ServicioPagar::find($validated['servicio_pagar_id']);
        $servicioPagar->estado = 'pagado';
        $servicioPagar->save();

        // Preparar datos adicionales para el comprobante
        $datosComprobante = [
            'forma_pago' => $validated['forma_pago'],
            'importe' => $validated['importe'],
            'fechaPago' => date('d/m/Y H:i'),
        ];

        // Enviar el comprobante por correo
        EnviarComprobantePagoEmailJob::dispatch(
            $servicioPagar->id, 
            $datosComprobante
        );

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado exitosamente. Se enviará un comprobante por correo.',
        ]);
    }
}


// ============================================================================
// EJEMPLO 5: Enviar comprobante con retraso
// ============================================================================
// Útil si quieres enviar el correo después de un tiempo específico

$idServicioPagar = 123;

// Enviar el correo después de 5 minutos
EnviarComprobantePagoEmailJob::dispatch($idServicioPagar)
    ->delay(now()->addMinutes(5));


// ============================================================================
// EJEMPLO 6: Manejo de errores personalizado
// ============================================================================
// Configurar reintentos y manejo de fallos

use App\Jobs\EnviarComprobantePagoEmailJob;

$idServicioPagar = 123;

EnviarComprobantePagoEmailJob::dispatch($idServicioPagar)
    ->onQueue('emails')
    ->delay(now()->addSeconds(30))  // Esperar 30 segundos antes de enviar
    ->onConnection('redis');         // Usar conexión Redis para la cola


// ============================================================================
// EJEMPLO 7: Uso en un webhook de Mercado Pago
// ============================================================================
// Enviar comprobante automáticamente cuando se confirma un pago

namespace App\Http\Controllers;

use App\Jobs\EnviarComprobantePagoEmailJob;
use App\Models\ServicioPagar;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Procesar la notificación de Mercado Pago
        $paymentId = $request->input('data.id');
        
        // Buscar el servicio asociado al pago
        $servicioPagar = ServicioPagar::where('mp_payment_id', $paymentId)->first();
        
        if ($servicioPagar && $servicioPagar->estado === 'pagado') {
            // Enviar comprobante por correo
            EnviarComprobantePagoEmailJob::dispatch($servicioPagar->id, [
                'forma_pago' => 'Mercado Pago',
                'mp_payment_id' => $paymentId,
            ]);
        }
        
        return response()->json(['status' => 'ok']);
    }
}


// ============================================================================
// EJEMPLO 8: Enviar múltiples comprobantes en lote
// ============================================================================
// Útil para enviar comprobantes de varios pagos procesados

use App\Jobs\EnviarComprobantePagoEmailJob;
use Illuminate\Support\Facades\Bus;

$idsPagosRealizados = [123, 124, 125, 126];

// Crear un batch de jobs
$jobs = [];
foreach ($idsPagosRealizados as $idPago) {
    $jobs[] = new EnviarComprobantePagoEmailJob($idPago);
}

// Despachar todos los jobs en batch
Bus::batch($jobs)
    ->onQueue('emails')
    ->name('Envío de comprobantes de pago')
    ->dispatch();


// ============================================================================
// EJEMPLO 9: Uso con datos completos personalizados (múltiples formas de pago)
// ============================================================================

$idServicioPagar = 123;

$datosComprobante = [
    'forma_pago' => 'Efectivo',
    'importe' => 1000.00,
    'forma_pago2' => 'Transferencia',
    'importe2' => 500.00,
    'comentario' => 'Pago mixto - Efectivo + Transferencia',
    'fechaPago' => '15/12/2024 14:30',
];

EnviarComprobantePagoEmailJob::dispatch($idServicioPagar, $datosComprobante);


// ============================================================================
// EJEMPLO 10: Testear el envío localmente
// ============================================================================
// Para probar en desarrollo sin encolar

use App\Jobs\EnviarComprobantePagoEmailJob;

$idServicioPagar = 1; // Usar un ID que exista en tu base de datos de desarrollo

// Ejecutar inmediatamente sin encolar
EnviarComprobantePagoEmailJob::dispatchSync($idServicioPagar);

// O ejecutar directamente el método handle
$job = new EnviarComprobantePagoEmailJob($idServicioPagar);
$job->handle();


// ============================================================================
// NOTAS IMPORTANTES:
// ============================================================================
// 
// 1. El Job automáticamente obtiene los siguientes datos de la BD:
//    - Información del cliente (nombre, DNI, correo)
//    - Información del servicio (nombre, empresa)
//    - Datos del pago (cantidad, precio, total, estado)
//    - Fecha del pago
//
// 2. Datos que puedes proporcionar opcionalmente en $datosPago:
//    - forma_pago: string (ej: "Efectivo", "Transferencia", "Mercado Pago")
//    - importe: numeric (importe de la primera forma de pago)
//    - forma_pago2: string (segunda forma de pago, opcional)
//    - importe2: numeric (importe de la segunda forma de pago, opcional)
//    - comentario: string (comentario adicional para el comprobante)
//    - fechaPago: string (formato d/m/Y H:i)
//    - logoEmpresa: string (URL o ruta del logo)
//
// 3. El PDF se genera automáticamente usando la vista: 
//    resources/views/pdf/comprobante_pago.blade.php
//
// 4. El correo HTML usa la vista:
//    resources/views/Correos/ComprobantePagoMail.blade.php
//
// 5. Si el cliente no tiene correo registrado, el Job lanzará una excepción
//
// 6. Los errores se registran automáticamente en el log de Laravel
//
// 7. Para que funcione correctamente, asegúrate de tener configurado:
//    - El servicio de correo en .env (MAIL_*)
//    - Las colas si usas dispatch() en lugar de dispatchSync()
//    - La librería dompdf para generar PDFs
//
