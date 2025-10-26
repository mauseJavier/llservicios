<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServicioPagar;
use App\Models\Pagos;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Manejar notificaciones de webhook de MercadoPago
     */
    public function handleNotification(Request $request)
    {
        try {
            // Log de la notificación recibida
            Log::info('Webhook MercadoPago recibido', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // Obtener datos del webhook
            $data = $request->input('data');
            $type = $request->input('type');
            
            // Solo procesar notificaciones de pago
            if ($type !== 'payment') {
                Log::info('Webhook ignorado - tipo no es payment', ['type' => $type]);
                return response()->json(['status' => 'ok'], 200);
            }

            $paymentId = $data['id'] ?? null;
            if (!$paymentId) {
                Log::warning('Webhook sin payment ID');
                return response()->json(['error' => 'Payment ID missing'], 400);
            }

            // Configurar SDK (usar credenciales por defecto)
            $accessToken = config('services.mercadopago.access_token');
            if (!$accessToken) {
                Log::error('Credenciales de MercadoPago no configuradas');
                return response()->json(['error' => 'Credentials not configured'], 500);
            }

            MercadoPagoConfig::setAccessToken($accessToken);
            MercadoPagoConfig::setRuntimeEnviroment(
                config('services.mercadopago.sandbox', true) 
                    ? MercadoPagoConfig::LOCAL 
                    : MercadoPagoConfig::SERVER
            );

            // Obtener información del pago
            $paymentClient = new PaymentClient();
            $payment = $paymentClient->get($paymentId);

            Log::info('Pago obtenido via webhook', [
                'payment_id' => $paymentId,
                'status' => $payment->status,
                'external_reference' => $payment->external_reference
            ]);

            // Buscar el servicio por referencia externa
            if ($payment->external_reference) {
                $externalReference = $payment->external_reference;
                // Extraer ID del servicio de la referencia (formato: "servicio_pagar_123")
                if (preg_match('/servicio_pagar_(\d+)/', $externalReference, $matches)) {
                    $servicioId = $matches[1];
                    $servicioPagar = ServicioPagar::find($servicioId);

                    if ($servicioPagar) {
                        $this->processPaymentNotification($servicioPagar, $payment);
                    } else {
                        Log::warning('ServicioPagar no encontrado', ['servicio_id' => $servicioId]);
                    }
                }
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (MPApiException $e) {
            Log::error('Error de API MercadoPago en webhook', [
                'payment_id' => $paymentId ?? null,
                'status_code' => $e->getApiResponse()->getStatusCode(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'API error'], 500);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook MercadoPago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Procesar notificación de pago
     */
    private function processPaymentNotification(ServicioPagar $servicioPagar, $payment)
    {
        $paymentStatus = $payment->status;
        $paymentId = $payment->id;

        Log::info('Procesando notificación de pago', [
            'servicio_pagar_id' => $servicioPagar->id,
            'payment_id' => $paymentId,
            'status' => $paymentStatus,
            'current_estado' => $servicioPagar->estado
        ]);

        switch ($paymentStatus) {
            case 'approved':
                if ($servicioPagar->estado !== 'pago') {
                    $servicioPagar->update([
                        'estado' => 'pago',
                        'mp_payment_id' => $paymentId
                    ]);

                    // Buscar el id de la forma de pago MercadoPago
                    $formaPago = \App\Models\FormaPago::where('nombre', 'MercadoPago')->first();
                    $formaPagoId = $formaPago ? $formaPago->id : 1; // fallback a 1 si no existe

                    // Crear registro en la tabla pagos si no existe, usando firstOrCreate para evitar duplicados
                    $pago = Pagos::firstOrCreate(
                        [
                            'id_servicio_pagar' => $servicioPagar->id,
                            'forma_pago' => $formaPagoId
                        ],
                        [
                            'id_usuario' => \App\Models\User::where('email','like', '%pago%')->value('id') ?? 1, // Ajustar según tu lógica
                            'importe' => $payment->transaction_amount,
                            'comentario' => 'Pago confirmado via webhook. Payment ID: ' . $paymentId
                        ]
                    );

                    if ($pago->wasRecentlyCreated) {
                        Log::info('Pago registrado exitosamente via webhook', [
                            'servicio_pagar_id' => $servicioPagar->id,
                            'payment_id' => $paymentId
                        ]);
                    }
                }
                break;

            case 'rejected':
            case 'cancelled':
                $servicioPagar->update([
                    'estado' => 'impago',
                    'mp_payment_id' => null
                ]);
                Log::info('Pago rechazado/cancelado', [
                    'servicio_pagar_id' => $servicioPagar->id,
                    'payment_id' => $paymentId
                ]);
                break;

            case 'pending':
            case 'in_process':
                $servicioPagar->update([
                    'mp_payment_id' => $paymentId
                ]);
                Log::info('Pago pendiente', [
                    'servicio_pagar_id' => $servicioPagar->id,
                    'payment_id' => $paymentId
                ]);
                break;

            default:
                Log::info('Estado de pago no manejado', [
                    'status' => $paymentStatus,
                    'payment_id' => $paymentId
                ]);
        }
    }
}