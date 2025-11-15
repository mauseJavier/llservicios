<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MercadoPagoQROrder;
use App\Services\MercadoPago\MercadoPagoQRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Recibir notificaciones de pagos QR
     */
    public function handleQRWebhook(Request $request)
    {
        try {
            Log::info('Webhook QR recibido', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            $data = $request->all();

            // Validar que sea una notificación de pago
            if (!isset($data['action']) || $data['action'] !== 'payment.created') {
                return response()->json(['status' => 'ignored'], 200);
            }

            // Obtener el ID del pago
            $paymentId = $data['data']['id'] ?? null;

            // Anotar en el log el payment ID recibido
            Log::info('Procesando webhook para payment ID', [
                'payment_id' => $paymentId
            ]);
            
            if (!$paymentId) {
                Log::warning('Webhook sin payment ID');
                return response()->json(['status' => 'ignored'], 200);
            }

            // Obtener información completa del pago
            $qrService = new MercadoPagoQRService();
            $paymentInfo = $qrService->getPayment($paymentId);

            if (!$paymentInfo['success']) {
                Log::error('Error obteniendo información del pago', [
                    'payment_id' => $paymentId
                ]);
                return response()->json(['status' => 'error'], 500);
            }

            $payment = $paymentInfo['data'];

            // Buscar la orden por external_reference
            $externalReference = $payment['external_reference'] ?? null;
            
            if (!$externalReference) {
                Log::warning('Pago sin external_reference', [
                    'payment_id' => $paymentId
                ]);
                return response()->json(['status' => 'ignored'], 200);
            }

            $order = MercadoPagoQROrder::where('external_reference', $externalReference)->first();

            if (!$order) {
                Log::warning('Orden no encontrada', [
                    'external_reference' => $externalReference,
                    'payment_id' => $paymentId
                ]);
                return response()->json(['status' => 'ignored'], 200);
            }

            // Actualizar estado de la orden según el estado del pago
            if ($payment['status'] === 'approved') {
                $order->markAsPaid($paymentId, $payment);
                
                Log::info('Orden marcada como pagada', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId
                ]);

                // Ejecutar lógica de confirmación de pago
                $this->confirmarPagoQR($order, $payment);
                
            } else {
                $order->update([
                    'payment_id' => $paymentId,
                    'payment_status' => $payment['status'],
                    'notification_data' => $payment,
                ]);
                
                Log::info('Estado de pago actualizado', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId,
                    'status' => $payment['status']
                ]);
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook QR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Confirmar pago del servicio cuando el pago QR es exitoso
     */
    private function confirmarPagoQR($order, $paymentData)
    {
        try {
            // Verificar que la orden tenga un servicio_pagar_id
            if (!$order->servicio_pagar_id) {
                Log::warning('Orden sin servicio_pagar_id', ['order_id' => $order->id]);
                return;
            }

            $servicioPagar = \App\Models\ServicioPagar::with(['servicio', 'cliente'])->find($order->servicio_pagar_id);

            if (!$servicioPagar) {
                Log::error('ServicioPagar no encontrado', ['servicio_pagar_id' => $order->servicio_pagar_id]);
                return;
            }

            // Verificar si ya está pagado para evitar duplicados
            if ($servicioPagar->estado === 'pago') {
                Log::info('ServicioPagar ya está marcado como pago', ['servicio_pagar_id' => $servicioPagar->id]);
                return;
            }

            $empresa = \App\Models\Empresa::find($servicioPagar->servicio->empresa_id);
            $cliente = $servicioPagar->cliente;

            // Buscar la forma de pago de MercadoPago
            $formaPagoMP = \App\Models\FormaPago::where('nombre', 'LIKE', '%MercadoPago%')
                ->orWhere('nombre', 'LIKE', '%Mercado Pago%')
                ->first();

            if (!$formaPagoMP) {
                // Crear forma de pago si no existe
                $formaPagoMP = \App\Models\FormaPago::create([
                    'nombre' => 'MercadoPago QR',
                    'descripcion' => 'Pago mediante código QR de MercadoPago'
                ]);
            }

            // Actualizar el estado del servicio a pagado
            $servicioPagar->update([
                'estado' => 'pago',
                'mp_payment_id' => $order->payment_id,
                'updated_at' => now()
            ]);

            // Preparar datos del pago
            $pago = [
                'idServicioPagar' => $servicioPagar->id,
                'idUsuario' => 1, // Usuario sistema para pagos automáticos
                'importe' => $order->total_amount,
                'forma_pago' => $formaPagoMP->id,
                'forma_pago2' => null,
                'importe2' => 0,
                'comentario' => 'Pago automático vía QR MercadoPago. Payment ID: ' . $order->payment_id
            ];

            // Disparar evento de pago
            \App\Events\PagoServicioEvent::dispatch($pago);

            // Enviar notificación por WhatsApp al cliente
            if ($cliente && $cliente->telefono && $empresa) {
                $mensaje = "Hola {$cliente->nombre},\n\n";
                $mensaje .= "✅ ¡Pago recibido exitosamente!\n\n";
                $mensaje .= "Detalles del pago:\n";
                $mensaje .= "• Servicio: {$servicioPagar->servicio->nombre}\n";
                $mensaje .= "• Forma de pago: MercadoPago QR\n";
                $mensaje .= "• Importe: \${$order->total_amount}\n";
                $mensaje .= "• Fecha: " . now()->format('d/m/Y H:i') . "\n";
                $mensaje .= "• ID de pago: {$order->payment_id}\n\n";
                $mensaje .= "¡Gracias por su preferencia!";

                $datosWA = [
                    'phoneNumber' => $cliente->telefono,
                    'message' => $mensaje,
                    'type' => 'text',
                    'additionalData' => [],
                    'instanciaWS' => $empresa->instanciaWS ?? null,
                    'tokenWS' => $empresa->tokenWS ?? null
                ];

                \App\Jobs\EnviarWhatsAppJob::dispatch($datosWA);

                // Generar y enviar comprobante PDF
                $datosPDF = [
                    'nombreCliente' => $cliente->nombre,
                    'dniCliente' => $cliente->dni,
                    'nombreServicio' => $servicioPagar->servicio->nombre,
                    'nombreEmpresa' => $empresa->nombre,
                    'cantidad' => $servicioPagar->cantidad,
                    'precioUnitario' => $servicioPagar->precio,
                    'forma_pago' => 'MercadoPago QR',
                    'importe' => $order->total_amount,
                    'forma_pago2' => null,
                    'importe2' => 0,
                    'comentario' => 'Pago automático vía QR. ID: ' . $order->payment_id,
                    'fechaPago' => now()->format('d/m/Y H:i'),
                    'logoEmpresa' => $empresa->logo ?? null,
                ];

                // Usar el método del controlador para generar el PDF
                $controlador = new \App\Http\Controllers\ServicioPagarController();
                $pdfBase64 = $controlador->GenerarComprobantePagoPDFBase64($datosPDF);

                $datosPDFWA = [
                    'phoneNumber' => $cliente->telefono,
                    'message' => 'Comprobante de Pago adjunto.',
                    'type' => 'document',
                    'additionalData' => [
                        'filename' => 'comprobante_pago_' . $servicioPagar->id . '.pdf',
                        'caption' => 'Comprobante de Pago - MercadoPago QR',
                        'base64' => $pdfBase64
                    ],
                    'instanciaWS' => $empresa->instanciaWS ?? null,
                    'tokenWS' => $empresa->tokenWS ?? null
                ];

                \App\Jobs\EnviarWhatsAppJob::dispatch($datosPDFWA);
            }

            Log::info('Pago QR confirmado exitosamente', [
                'servicio_pagar_id' => $servicioPagar->id,
                'order_id' => $order->id,
                'payment_id' => $order->payment_id,
                'amount' => $order->total_amount
            ]);

        } catch (\Exception $e) {
            Log::error('Error confirmando pago QR', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
