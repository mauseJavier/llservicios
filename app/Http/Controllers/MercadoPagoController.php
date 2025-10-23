<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MercadoPagoController extends Controller
{
    protected $mercadoPagoService;

    public function __construct(MercadoPagoService $mercadoPagoService)
    {
        $this->mercadoPagoService = $mercadoPagoService;
    }

    /**
     * Mostrar formulario de pago de demostración
     */
    public function showPaymentForm()
    {
        return view('mercadopago.payment-form');
    }

    /**
     * Crear preferencia de pago y redirigir a Checkout Pro
     */
    public function createPreference(Request $request)
    {
        try {
            // Validar los datos de entrada
            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
                'items.*.title' => 'required|string',
                'items.*.price' => 'required|numeric|min:0.01',
                'items.*.quantity' => 'integer|min:1',
                'payer.email' => 'email',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 400);
            }

            // Preparar los datos para MercadoPago
            $items = MercadoPagoService::createItems($request->input('items'));
            
            $preferenceData = [
                'items' => $items,
                'external_reference' => $request->input('external_reference', 'ORDER-' . time()),
                'back_urls' => [
                    'success' => $request->input('success_url', config('app.url') . '/mercadopago/success'),
                    'failure' => $request->input('failure_url', config('app.url') . '/mercadopago/failure'),
                    'pending' => $request->input('pending_url', config('app.url') . '/mercadopago/pending'),
                ],
                'auto_return' => 'approved',
                'notification_url' => config('app.url') . '/mercadopago/webhook',
            ];

            // Agregar información del pagador si está disponible
            if ($request->has('payer')) {
                $preferenceData['payer'] = MercadoPagoService::createPayer($request->input('payer'));
            }

            dd($preferenceData);

            // Crear la preferencia
            $result = $this->mercadoPagoService->createPreference($preferenceData);

            if ($result['success']) {
                if ($request->expectsJson()) {
                    return response()->json($result);
                } else {
                    // Redirigir al checkout de MercadoPago
                    $checkoutUrl = config('services.mercadopago.sandbox') 
                        ? $result['sandbox_init_point'] 
                        : $result['init_point'];
                    
                    return redirect($checkoutUrl);
                }
            } else {
                return response()->json($result, 500);
            }

        } catch (\Exception $e) {
            Log::error('Error creating MercadoPago preference: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Manejar notificaciones webhook de MercadoPago
     */
    public function webhook(Request $request)
    {
        try {
            Log::info('MercadoPago Webhook received', $request->all());

            $topic = $request->input('topic');
            $id = $request->input('id');

            if ($topic === 'payment') {
                $payment = $this->mercadoPagoService->getPayment($id);
                
                if ($payment['success']) {
                    // Aquí puedes agregar tu lógica para procesar el pago
                    // Por ejemplo, actualizar el estado del pedido en la base de datos
                    $this->processPayment($payment['data']);
                }
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing MercadoPago webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Página de éxito después del pago
     */
    public function success(Request $request)
    {
        $paymentId = $request->input('payment_id');
        $status = $request->input('status');
        $externalReference = $request->input('external_reference');

        return view('mercadopago.success', compact('paymentId', 'status', 'externalReference'));
    }

    /**
     * Página de pago pendiente
     */
    public function pending(Request $request)
    {
        $paymentId = $request->input('payment_id');
        $status = $request->input('status');
        $externalReference = $request->input('external_reference');

        return view('mercadopago.pending', compact('paymentId', 'status', 'externalReference'));
    }

    /**
     * Página de pago fallido
     */
    public function failure(Request $request)
    {
        $paymentId = $request->input('payment_id');
        $status = $request->input('status');
        $externalReference = $request->input('external_reference');

        return view('mercadopago.failure', compact('paymentId', 'status', 'externalReference'));
    }

    /**
     * Procesar el pago recibido (personalizar según tus necesidades)
     */
    private function processPayment($paymentData)
    {
        // Aquí puedes agregar tu lógica personalizada
        // Por ejemplo:
        // - Actualizar el estado del pedido
        // - Enviar emails de confirmación
        // - Actualizar inventario
        // - etc.

        Log::info('Processing payment', [
            'payment_id' => $paymentData->id,
            'status' => $paymentData->status,
            'external_reference' => $paymentData->external_reference,
            'transaction_amount' => $paymentData->transaction_amount,
        ]);
    }
}
