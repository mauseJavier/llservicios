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
}
