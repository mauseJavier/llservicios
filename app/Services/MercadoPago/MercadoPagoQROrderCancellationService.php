<?php

namespace App\Services\MercadoPago;

use App\Models\MercadoPagoPOS;
use App\Models\MercadoPagoQROrder;
use Illuminate\Support\Facades\Log;

/**
 * Cancelación de órdenes QR.
 *
 * MercadoPago no siempre puede eliminar la orden de la caja (por ejemplo,
 * cuando ya expiró, cuando nunca quedó registrada como pendiente o cuando la
 * cuenta solo tiene habilitado el endpoint legacy). Cancelar en MercadoPago es
 * un "mejor esfuerzo": lo que manda es dejar la orden local cancelada para que
 * la pantalla deje de esperar el pago.
 *
 * Si el cliente llegara a pagar de todos modos, el webhook o la reconciliación
 * igual confirman el pago por external_reference, así que no se pierde plata.
 */
class MercadoPagoQROrderCancellationService
{
    public function __construct(private MercadoPagoQRPaymentService $paymentService) {}

    /**
     * Cancelar una orden QR.
     *
     * @return array{success: bool, status: string, message: string, payment_id?: ?string, amount?: float, upstream_cancelled: bool}
     */
    public function cancel(MercadoPagoQROrder $order): array
    {
        $order->loadMissing('pos.store.empresa');

        if ($order->isPaid()) {
            return [
                'success' => true,
                'status' => 'paid',
                'message' => 'El pago ya fue acreditado; la orden no se puede cancelar.',
                'payment_id' => $order->payment_id,
                'amount' => (float) $order->total_amount,
                'upstream_cancelled' => false,
            ];
        }

        if (! $order->isPending()) {
            return [
                'success' => true,
                'status' => (string) $order->status,
                'message' => 'La orden ya estaba cerrada.',
                'upstream_cancelled' => false,
            ];
        }

        $pos = $order->pos;
        $empresa = $pos?->store?->empresa;

        if (! $pos || ! $empresa) {
            return $this->cancelarLocal(
                $order,
                'La orden no tiene una caja o empresa asociada; se canceló localmente.'
            );
        }

        if (empty($empresa->MP_ACCESS_TOKEN) || empty($empresa->MP_USER_ID)) {
            return $this->cancelarLocal(
                $order,
                'La empresa no tiene credenciales de Mercado Pago; se canceló localmente.'
            );
        }

        $qrService = new MercadoPagoQRService;
        $qrService->setAccessToken((string) $empresa->MP_ACCESS_TOKEN);
        $qrService->setUserId((string) $empresa->MP_USER_ID);

        // Si el pago ya se acreditó (webhook demorado o caído) no hay que
        // cancelar nada: se confirma el pago y se informa.
        try {
            if ($this->paymentService->reconcile($order)) {
                $order->refresh();

                return [
                    'success' => true,
                    'status' => 'paid',
                    'message' => 'El pago ya fue acreditado; la orden no se puede cancelar.',
                    'payment_id' => $order->payment_id,
                    'amount' => (float) $order->total_amount,
                    'upstream_cancelled' => false,
                ];
            }
        } catch (\Throwable $e) {
            Log::error('MercadoPagoQR - Error verificando pago antes de cancelar', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        // MercadoPago mantiene una sola orden por caja. Antes de borrar hay
        // que asegurarse de que la orden activa sea la de esta orden y no la
        // de un cobro más nuevo.
        $consulta = $qrService->getQROrder((string) $pos->external_id);
        $ordenMp = $consulta['success'] ? ($consulta['data'] ?? null) : null;

        if (is_array($ordenMp) && ! $this->esLaMismaOrden($order, $ordenMp)) {
            return $this->cancelarLocal(
                $order,
                'Se canceló la orden localmente. En la caja hay otra orden activa en Mercado Pago, por lo que no se eliminó.'
            );
        }

        if ($ordenMp === null && ! empty($consulta['not_found'])) {
            return $this->cancelarLocal(
                $order,
                'La orden ya no estaba activa en Mercado Pago. Se canceló localmente.'
            );
        }

        // Si no se pudo consultar, solo se borra cuando esta es la última
        // orden pendiente de la caja (la que MercadoPago debería tener).
        if ($ordenMp === null && ! $this->esLaUltimaPendienteDeLaCaja($order)) {
            return $this->cancelarLocal(
                $order,
                'Se canceló la orden localmente. No se eliminó el QR de la caja porque pertenece a un cobro más nuevo.'
            );
        }

        $borrado = $qrService->deleteQROrder((string) $pos->external_id);

        if (! empty($borrado['success'])) {
            return $this->cancelarLocal($order, 'Orden cancelada correctamente.', true);
        }

        if (! empty($borrado['already_gone'])) {
            return $this->cancelarLocal(
                $order,
                'La orden ya no estaba activa en Mercado Pago. Se canceló localmente.'
            );
        }

        // Falla real del upstream: la orden local se cancela igual, pero se
        // avisa que MercadoPago no confirmó la eliminación del QR.
        Log::error('MercadoPagoQR - Mercado Pago no confirmó la eliminación del QR', [
            'order_id' => $order->id,
            'pos_id' => $pos->external_id,
            'error_code' => $borrado['error_code'] ?? null,
            'status_code' => $borrado['status_code'] ?? null,
        ]);

        return $this->cancelarLocal(
            $order,
            'Se canceló la orden localmente, pero Mercado Pago no confirmó la eliminación del QR.'
        );
    }

    /**
     * Cerrar las órdenes pendientes de una caja antes de generar una nueva.
     *
     * MercadoPago admite una sola orden activa por POS, así que las anteriores
     * quedan como fantasmas si no se cierran localmente.
     */
    public function expirePreviousPendingOrders(MercadoPagoPOS $pos, ?int $exceptOrderId = null): int
    {
        $pendientes = MercadoPagoQROrder::where('mercadopago_pos_id', $pos->id)
            ->where('status', 'pending')
            ->when($exceptOrderId, fn ($query) => $query->where('id', '!=', $exceptOrderId))
            ->get();

        $expiradas = 0;

        foreach ($pendientes as $pendiente) {
            // Las que ya vencieron hace rato no necesitan consulta a la API.
            if ($pendiente->expires_at && $pendiente->expires_at->lt(now()->subMinutes(5))) {
                $pendiente->update(['status' => 'expired']);
                $expiradas++;

                continue;
            }

            try {
                if ($this->paymentService->reconcile($pendiente)) {
                    continue;
                }
            } catch (\Throwable $e) {
                Log::error('MercadoPagoQR - Error reconciliando orden previa', [
                    'order_id' => $pendiente->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $pendiente->update(['status' => 'expired']);
            $expiradas++;
        }

        if ($expiradas > 0) {
            Log::info('MercadoPagoQR - Órdenes pendientes previas cerradas', [
                'pos_id' => $pos->external_id,
                'cantidad' => $expiradas,
            ]);
        }

        return $expiradas;
    }

    /**
     * Verificar si la orden que informa MercadoPago es la que se quiere borrar.
     */
    private function esLaMismaOrden(MercadoPagoQROrder $order, array $ordenMp): bool
    {
        $referenciaMp = $ordenMp['external_reference'] ?? null;

        if (! empty($referenciaMp)) {
            return (string) $referenciaMp === (string) $order->external_reference;
        }

        $idMp = $ordenMp['in_store_order_id'] ?? $ordenMp['id'] ?? null;

        if (! empty($idMp) && ! empty($order->in_store_order_id)) {
            return (string) $idMp === (string) $order->in_store_order_id;
        }

        // Sin datos para comparar: se asume que es la misma.
        return true;
    }

    /**
     * Verificar si esta es la orden pendiente más nueva de la caja.
     */
    private function esLaUltimaPendienteDeLaCaja(MercadoPagoQROrder $order): bool
    {
        $ultimaId = MercadoPagoQROrder::where('mercadopago_pos_id', $order->mercadopago_pos_id)
            ->where('status', 'pending')
            ->max('id');

        return (int) $ultimaId === (int) $order->id;
    }

    private function cancelarLocal(MercadoPagoQROrder $order, string $message, bool $upstreamCancelled = false): array
    {
        $order->update(['status' => 'cancelled']);

        Log::info('MercadoPagoQR - Orden QR cancelada', [
            'order_id' => $order->id,
            'external_reference' => $order->external_reference,
            'upstream_cancelled' => $upstreamCancelled,
            'detalle' => $message,
        ]);

        return [
            'success' => true,
            'status' => 'cancelled',
            'message' => $message,
            'upstream_cancelled' => $upstreamCancelled,
        ];
    }
}
