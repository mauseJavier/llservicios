<?php

namespace App\Console\Commands;

use App\Http\Controllers\MercadoPago\MercadoPagoWebhookController;
use App\Models\MercadoPagoPagoIntento;
use App\Models\MercadoPagoPagoProcesado;
use App\Models\MercadoPagoQROrder;
use App\Models\ServicioPagar;
use App\Services\MercadoPago\MercadoPagoApiService;
use App\Services\MercadoPago\MercadoPagoQRPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconciliarPagosMercadoPago extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mp:reconciliar-pagos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcilia pagos de MercadoPago aprobados que no se procesaron por webhook/back_url';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando reconciliación de pagos MercadoPago...');

        $apiService = new MercadoPagoApiService;
        $webhookController = app(MercadoPagoWebhookController::class);

        // Órdenes QR pendientes: si el webhook no llegó, se confirman acá.
        $qrConfirmadas = $this->reconciliarOrdenesQr();

        // Servicios impagos que podrían tener un pago aprobado pendiente de procesar.
        $serviciosImpagos = ServicioPagar::with('servicio.empresa', 'cliente')
            ->where('estado', 'impago')
            ->get();

        // Deduplicar búsquedas por (empresa_id, external_reference). Cada deuda usa
        // su referencia individual única y cada link agrupado su referencia de intento
        // (lote_{token}), también única. Ya no se usa la referencia genérica
        // cliente_impagos_{cliente}_{empresa}, que causaba el cruce entre meses.
        $busquedas = [];

        foreach ($serviciosImpagos as $servicioPagar) {
            $empresa = $servicioPagar->servicio->empresa ?? null;

            if (! $empresa || empty($empresa->MP_ACCESS_TOKEN)) {
                continue;
            }

            $empresaId = (int) $empresa->id;

            $busquedas[$empresaId]['servicio_pagar_'.$servicioPagar->id] = (string) $empresa->MP_ACCESS_TOKEN;
        }

        $intentosPendientes = MercadoPagoPagoIntento::with('empresa')
            ->where('estado', 'pendiente')
            ->get();

        foreach ($intentosPendientes as $intento) {
            $empresa = $intento->empresa;

            if (! $empresa || empty($empresa->MP_ACCESS_TOKEN)) {
                continue;
            }

            $empresaId = (int) $empresa->id;

            $busquedas[$empresaId][$intento->referencia] = (string) $empresa->MP_ACCESS_TOKEN;
        }

        if (empty($busquedas)) {
            $this->info("No hay servicios impagos con empresa y token configurados. Ordenes QR confirmadas: {$qrConfirmadas}.");

            return 0;
        }

        $pagosProcesados = 0;
        $errores = 0;
        $busquedasRealizadas = 0;

        foreach ($busquedas as $empresaId => $referencias) {
            foreach ($referencias as $externalReference => $token) {
                $busquedasRealizadas++;

                $paymentIds = $apiService->searchApprovedPayments($externalReference, $token);

                if (empty($paymentIds)) {
                    continue;
                }

                foreach ($paymentIds as $paymentId) {
                    $this->line("Procesando pago {$paymentId} (ref: {$externalReference})...");

                    if (MercadoPagoPagoProcesado::where('payment_id', (string) $paymentId)->exists()) {
                        $this->line("Pago {$paymentId} ya procesado; se omite.");

                        continue;
                    }

                    $procesado = $webhookController->procesarPagoDesdeRetorno((string) $paymentId, $externalReference);

                    if ($procesado) {
                        $pagosProcesados++;
                        Log::info('Reconciliación: pago procesado', [
                            'payment_id' => $paymentId,
                            'external_reference' => $externalReference,
                            'empresa_id' => $empresaId,
                        ]);
                    } else {
                        $errores++;
                        Log::warning('Reconciliación: pago no procesado', [
                            'payment_id' => $paymentId,
                            'external_reference' => $externalReference,
                            'empresa_id' => $empresaId,
                        ]);
                    }
                }
            }
        }

        $this->info("Reconciliación finalizada: busquedas={$busquedasRealizadas}, pagos_procesados={$pagosProcesados}, ordenes_qr_confirmadas={$qrConfirmadas}, errores={$errores}");

        return 0;
    }

    /**
     * Confirmar órdenes QR pendientes cuyo pago existe en MercadoPago pero
     * cuyo webhook nunca llegó.
     */
    private function reconciliarOrdenesQr(): int
    {
        $service = app(MercadoPagoQRPaymentService::class);
        $confirmadas = 0;

        $ordenes = MercadoPagoQROrder::with('pos.store.empresa')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->limit(50)
            ->get();

        foreach ($ordenes as $orden) {
            if ($service->reconcile($orden)) {
                $confirmadas++;

                $this->line("Orden QR {$orden->id} confirmada (payment ".($orden->fresh()->payment_id ?? 'N/D').').');

                Log::info('Reconciliación: orden QR confirmada', [
                    'order_id' => $orden->id,
                    'external_reference' => $orden->external_reference,
                ]);
            }
        }

        return $confirmadas;
    }
}
