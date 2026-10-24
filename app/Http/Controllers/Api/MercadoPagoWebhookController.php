<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\MercadoPagoQROrder;
use App\Services\MercadoPago\MercadoPagoQRPaymentService;
use App\Services\MercadoPago\MercadoPagoQRService;
use App\Services\MercadoPago\MercadoPagoWebhookSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private MercadoPagoWebhookSignatureService $webhookSignature,
        private MercadoPagoQRPaymentService $qrPayments
    ) {}

    /**
     * Recibir notificaciones de pagos QR
     */
    public function handleQRWebhook(Request $request)
    {
        try {
            Log::info('Webhook QR recibido', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
            ]);

            $this->verificarFirmaBestEffort($request);

            $data = $request->all();

            // Aceptar tanto el formato "action: payment.created" como los
            // tópicos legacy (type/topic: payment) e IPN (topic + id).
            $tipo = $data['type'] ?? $data['topic'] ?? null;
            $accion = $data['action'] ?? null;

            $esPago = $tipo === 'payment'
                || (is_string($accion) && str_starts_with($accion, 'payment.'));

            if (! $esPago) {
                return response()->json(['status' => 'ignored'], 200);
            }

            // Obtener el ID del pago
            $paymentId = $data['data']['id'] ?? $data['id'] ?? null;

            // Anotar en el log el payment ID recibido
            Log::info('Procesando webhook para payment ID', [
                'payment_id' => $paymentId,
            ]);

            if (! $paymentId) {
                Log::warning('Webhook sin payment ID');

                return response()->json(['status' => 'ignored'], 200);
            }

            // Obtener información completa del pago con el token de la empresa
            // dueña de la notificación (o probando con cada empresa configurada).
            $qrService = new MercadoPagoQRService;
            $paymentInfo = $this->obtenerPago(
                $qrService,
                (string) $paymentId,
                $this->webhookSignature->resolverEmpresa($request)
            );

            if (! $paymentInfo['success']) {
                Log::error('Error obteniendo información del pago', [
                    'payment_id' => $paymentId,
                ]);

                return response()->json(['status' => 'error'], 500);
            }

            $payment = $paymentInfo['data'];
            $empresaPago = $paymentInfo['empresa'] ?? null;

            // Buscar la orden por external_reference
            $externalReference = $payment['external_reference'] ?? null;

            if (! $externalReference) {
                Log::warning('Pago sin external_reference', [
                    'payment_id' => $paymentId,
                ]);

                return response()->json(['status' => 'ignored'], 200);
            }

            $order = MercadoPagoQROrder::where('external_reference', $externalReference)->first();

            if (! $order) {
                Log::warning('Orden no encontrada', [
                    'external_reference' => $externalReference,
                    'payment_id' => $paymentId,
                ]);

                return response()->json(['status' => 'ignored'], 200);
            }

            // Actualizar estado de la orden según el estado del pago
            if ($payment['status'] === 'approved') {
                $empresaOrden = $order->pos->store->empresa_id ?? null;

                if ($empresaOrden && $empresaPago && (int) $empresaOrden !== (int) $empresaPago->id) {
                    Log::error('Pago QR de una empresa distinta a la dueña de la orden', [
                        'payment_id' => $paymentId,
                        'external_reference' => $externalReference,
                        'empresa_orden' => $empresaOrden,
                        'empresa_pago' => $empresaPago->id,
                    ]);

                    return response()->json(['status' => 'ignored'], 200);
                }

                if (! $this->qrPayments->confirm($order, $payment)) {
                    Log::error('No se pudo confirmar el pago QR', [
                        'payment_id' => $paymentId,
                        'order_id' => $order->id,
                    ]);

                    return response()->json(['status' => 'ignored'], 200);
                }

                Log::info('Orden marcada como pagada', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId,
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
                    'status' => $payment['status'],
                ]);
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook QR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Validar la firma solo si el header viene presente. Las notificaciones de
     * QR legacy no están firmadas por Mercado Pago, por lo que un fallo no debe
     * bloquear el procesamiento (sí queda registrado para auditoría).
     */
    private function verificarFirmaBestEffort(Request $request): void
    {
        $verificacion = $this->webhookSignature->verificar($request);

        if ($verificacion['estado'] === MercadoPagoWebhookSignatureService::ESTADO_VALIDA) {
            return;
        }

        Log::info('Webhook QR sin firma válida (no bloqueante)', [
            'estado' => $verificacion['estado'],
            'motivo' => $verificacion['motivo'],
            'empresa_id' => $verificacion['empresa']->id ?? null,
            'request_id' => $request->header('x-request-id'),
            'data_id' => $this->webhookSignature->dataId($request),
        ]);
    }

    /**
     * Obtener el pago usando primero el token de la empresa notificada y, si
     * falla, el de cada empresa con credenciales configuradas (multi-tenant).
     *
     * @return array{success: bool, data: mixed, empresa: ?Empresa, error?: string}
     */
    private function obtenerPago(MercadoPagoQRService $qrService, string $paymentId, ?Empresa $empresa): array
    {
        if ($empresa && ! empty($empresa->MP_ACCESS_TOKEN)) {
            $qrService->setAccessToken((string) $empresa->MP_ACCESS_TOKEN);
            $qrService->setUserId($empresa->MP_USER_ID ? (string) $empresa->MP_USER_ID : null);

            $resultado = $qrService->getPayment($paymentId);

            if ($resultado['success']) {
                return $resultado + ['empresa' => $empresa];
            }
        }

        $candidatas = Empresa::query()
            ->whereNotNull('MP_ACCESS_TOKEN')
            ->where('MP_ACCESS_TOKEN', '!=', '')
            ->get(['id', 'MP_ACCESS_TOKEN', 'MP_USER_ID']);

        foreach ($candidatas as $candidata) {
            if ($empresa && $candidata->id === $empresa->id) {
                continue;
            }

            $qrService->setAccessToken((string) $candidata->MP_ACCESS_TOKEN);
            $qrService->setUserId($candidata->MP_USER_ID ? (string) $candidata->MP_USER_ID : null);

            $resultado = $qrService->getPayment($paymentId);

            if ($resultado['success']) {
                return $resultado + ['empresa' => $candidata];
            }
        }

        return [
            'success' => false,
            'data' => null,
            'empresa' => null,
            'error' => 'No se pudo obtener el pago con ningún token configurado',
        ];
    }
}
