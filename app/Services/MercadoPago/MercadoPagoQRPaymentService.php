<?php

namespace App\Services\MercadoPago;

use App\Events\PagoServicioEvent;
use App\Http\Controllers\ServicioPagarController;
use App\Jobs\EnviarWhatsAppJob;
use App\Models\Empresa;
use App\Models\FormaPago;
use App\Models\MercadoPagoQROrder;
use App\Models\ServicioPagar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Confirmación de pagos de órdenes QR de MercadoPago.
 *
 * Centraliza la lógica que antes vivía en el webhook para que también pueda
 * usarse desde el polling de la pantalla del QR (reconciliación) sin duplicar
 * el pago del servicio.
 */
class MercadoPagoQRPaymentService
{
    public function __construct(private MercadoPagoApiService $api) {}

    /**
     * Buscar en MercadoPago un pago aprobado para la orden y confirmarlo.
     *
     * Se usa cuando el webhook no llegó (por ejemplo, ngrok caído o dominio
     * mal configurado) y la pantalla del QR sigue esperando confirmación.
     */
    public function reconcile(MercadoPagoQROrder $order): bool
    {
        if ($order->isPaid()) {
            return true;
        }

        $order->loadMissing('pos.store.empresa');
        $empresa = $order->pos?->store?->empresa;

        if (! $empresa || empty($empresa->MP_ACCESS_TOKEN)) {
            return false;
        }

        $paymentIds = $this->api->searchApprovedPayments(
            (string) $order->external_reference,
            (string) $empresa->MP_ACCESS_TOKEN
        );

        if (empty($paymentIds)) {
            return false;
        }

        $qrService = new MercadoPagoQRService;
        $qrService->setAccessToken((string) $empresa->MP_ACCESS_TOKEN);
        $qrService->setUserId($empresa->MP_USER_ID ? (string) $empresa->MP_USER_ID : null);

        foreach ($paymentIds as $paymentId) {
            $resultado = $qrService->getPayment((string) $paymentId);

            if (empty($resultado['success'])) {
                continue;
            }

            if ($this->confirm($order, $resultado['data'])) {
                Log::info('Pago QR reconciliado sin webhook', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentId,
                    'external_reference' => $order->external_reference,
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Confirmar el pago de una orden QR de forma idempotente.
     *
     * Marca la orden como pagada y, si corresponde, el servicio asociado.
     * Devuelve true si el pago quedó confirmado (o ya lo estaba).
     */
    public function confirm(MercadoPagoQROrder $order, array $payment): bool
    {
        if (! $this->paymentMatchesOrder($order, $payment)) {
            return false;
        }

        $resultado = DB::transaction(function () use ($order, $payment) {
            $orden = MercadoPagoQROrder::whereKey($order->id)->lockForUpdate()->first();

            if (! $orden) {
                return null;
            }

            if ($orden->isPaid()) {
                return ['already' => true];
            }

            $orden->markAsPaid((string) ($payment['id'] ?? ''), $payment);

            $servicio = null;

            if ($orden->servicio_pagar_id) {
                $servicio = ServicioPagar::with(['servicio', 'cliente'])
                    ->whereKey($orden->servicio_pagar_id)
                    ->lockForUpdate()
                    ->first();

                if ($servicio && $servicio->estado !== 'pago') {
                    $servicio->update([
                        'estado' => 'pago',
                        'mp_payment_id' => $orden->payment_id,
                    ]);
                } elseif ($servicio) {
                    // Otro pago ya marcó el servicio: no duplicar notificaciones.
                    $servicio = null;
                }
            }

            return ['already' => false, 'order' => $orden, 'servicio' => $servicio];
        });

        if ($resultado === null) {
            return false;
        }

        if ($resultado['already']) {
            return true;
        }

        if (! empty($resultado['servicio'])) {
            $this->notificarPago($resultado['order'], $resultado['servicio']);
        }

        return true;
    }

    /**
     * Verificar que el pago de MercadoPago corresponda a la orden.
     */
    private function paymentMatchesOrder(MercadoPagoQROrder $order, array $payment): bool
    {
        if (($payment['status'] ?? null) !== 'approved') {
            return false;
        }

        $reference = $payment['external_reference'] ?? null;

        if ($reference !== null && (string) $reference !== (string) $order->external_reference) {
            Log::error('Pago QR con external_reference distinto al de la orden', [
                'order_id' => $order->id,
                'external_reference_pago' => $reference,
                'external_reference_orden' => $order->external_reference,
            ]);

            return false;
        }

        $montoPagado = (float) ($payment['transaction_amount'] ?? 0);
        $montoEsperado = (float) $order->total_amount;

        if (abs($montoPagado - $montoEsperado) > 0.01) {
            Log::error('Pago QR con monto distinto al esperado', [
                'order_id' => $order->id,
                'monto_pagado' => $montoPagado,
                'monto_esperado' => $montoEsperado,
            ]);

            return false;
        }

        $order->loadMissing('pos.store.empresa');
        $empresa = $order->pos?->store?->empresa;
        $collector = $payment['collector_id'] ?? null;

        if ($empresa && $collector && ! empty($empresa->MP_USER_ID)
            && (string) $empresa->MP_USER_ID !== (string) $collector) {
            Log::error('Pago QR de una cuenta de MercadoPago distinta a la dueña de la orden', [
                'order_id' => $order->id,
                'collector_id' => $collector,
                'empresa_user_id' => $empresa->MP_USER_ID,
            ]);

            return false;
        }

        // El servicio debe pertenecer a la empresa dueña de la caja.
        if ($order->servicio_pagar_id) {
            $servicio = ServicioPagar::with('servicio')->find($order->servicio_pagar_id);
            $empresaServicio = $servicio?->servicio?->empresa_id;

            if ($empresa && $empresaServicio && (int) $empresaServicio !== (int) $empresa->id) {
                Log::error('Orden QR de una empresa distinta a la del servicio', [
                    'order_id' => $order->id,
                    'servicio_pagar_id' => $order->servicio_pagar_id,
                    'empresa_servicio' => $empresaServicio,
                    'empresa_orden' => $empresa->id,
                ]);

                return false;
            }
        }

        return true;
    }

    /**
     * Registrar el pago y notificar al cliente (WhatsApp + comprobante).
     */
    private function notificarPago(MercadoPagoQROrder $order, ServicioPagar $servicioPagar): void
    {
        try {
            $empresa = Empresa::find($servicioPagar->servicio->empresa_id);
            $cliente = $servicioPagar->cliente;

            if (! $empresa) {
                return;
            }

            $formaPagoMP = FormaPago::where('nombre', 'LIKE', '%MercadoPago%')
                ->orWhere('nombre', 'LIKE', '%Mercado Pago%')
                ->first();

            if (! $formaPagoMP) {
                $formaPagoMP = FormaPago::create([
                    'nombre' => 'MercadoPago QR',
                    'descripcion' => 'Pago mediante código QR de MercadoPago',
                ]);
            }

            $pago = [
                'idServicioPagar' => $servicioPagar->id,
                'idUsuario' => 1, // Usuario sistema para pagos automáticos
                'importe' => $order->total_amount,
                'forma_pago' => $formaPagoMP->id,
                'forma_pago2' => null,
                'importe2' => 0,
                'comentario' => 'Pago automático vía QR MercadoPago. Payment ID: '.$order->payment_id,
            ];

            PagoServicioEvent::dispatch($pago);

            if ($cliente && $cliente->telefono) {
                $mensaje = "Hola {$cliente->nombre},\n\n";
                $mensaje .= "Le informamos desde {$empresa->nombre} que hemos recibido su pago:\n\n";
                $mensaje .= "✅ ¡Pago recibido exitosamente!\n\n";
                $mensaje .= "Detalles del pago:\n";
                $mensaje .= "• Servicio: {$servicioPagar->servicio->nombre}\n";
                $mensaje .= "• Forma de pago: MercadoPago QR\n";
                $mensaje .= "• Importe: \${$order->total_amount}\n";
                $mensaje .= '• Fecha: '.now()->format('d/m/Y H:i')."\n";
                $mensaje .= "• ID de pago: {$order->payment_id}\n\n";
                $mensaje .= '¡Gracias por su preferencia!';

                EnviarWhatsAppJob::dispatch([
                    'phoneNumber' => $cliente->telefono,
                    'message' => $mensaje,
                    'type' => 'text',
                    'additionalData' => [],
                    'instanciaWS' => $empresa->instanciaWS ?? null,
                    'tokenWS' => $empresa->tokenWS ?? null,
                ]);

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
                    'comentario' => 'Pago automático vía QR. ID: '.$order->payment_id,
                    'fechaPago' => now()->format('d/m/Y H:i'),
                    'logoEmpresa' => $empresa->logo ?? null,
                ];

                $controlador = new ServicioPagarController;
                $pdfBase64 = $controlador->GenerarComprobantePagoPDFBase64($datosPDF);

                EnviarWhatsAppJob::dispatch([
                    'phoneNumber' => $cliente->telefono,
                    'message' => 'Comprobante de Pago adjunto - '.$empresa->nombre,
                    'type' => 'document',
                    'additionalData' => [
                        'filename' => 'comprobante_pago_'.$servicioPagar->id.'.pdf',
                        'caption' => 'Comprobante de Pago - MercadoPago QR - '.$empresa->nombre,
                        'base64' => $pdfBase64,
                    ],
                    'instanciaWS' => $empresa->instanciaWS ?? null,
                    'tokenWS' => $empresa->tokenWS ?? null,
                ]);
            }

            Log::info('Pago QR confirmado exitosamente', [
                'servicio_pagar_id' => $servicioPagar->id,
                'order_id' => $order->id,
                'payment_id' => $order->payment_id,
                'amount' => $order->total_amount,
            ]);
        } catch (\Throwable $e) {
            // La confirmación ya quedó persistida; un fallo de notificación
            // no debe revertir el pago.
            Log::error('Error notificando pago QR', [
                'order_id' => $order->id,
                'servicio_pagar_id' => $servicioPagar->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
