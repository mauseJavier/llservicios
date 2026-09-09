<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServicioPagar;
use App\Services\MercadoPago\MercadoPagoApiService;
use App\Http\Controllers\MercadoPago\MercadoPagoWebhookController;
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
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Iniciando reconciliación de pagos MercadoPago...');

        $apiService = new MercadoPagoApiService();
        $webhookController = app(MercadoPagoWebhookController::class);

        // Servicios impagos que podrían tener un pago aprobado pendiente de procesar.
        $serviciosImpagos = ServicioPagar::with('servicio.empresa', 'cliente')
            ->where('estado', 'impago')
            ->get();

        // Deduplicar búsquedas por (empresa_id, external_reference):
        // cada servicio genera su referencia individual y la agrupada de su cliente en la empresa.
        $busquedas = [];

        foreach ($serviciosImpagos as $servicioPagar) {
            $empresa = $servicioPagar->servicio->empresa ?? null;

            if (!$empresa || empty($empresa->MP_ACCESS_TOKEN)) {
                continue;
            }

            $empresaId = (int) $empresa->id;

            $busquedas[$empresaId]['servicio_pagar_' . $servicioPagar->id] = (string) $empresa->MP_ACCESS_TOKEN;
            $busquedas[$empresaId]['cliente_impagos_' . $servicioPagar->cliente_id . '_' . $empresaId] = (string) $empresa->MP_ACCESS_TOKEN;
        }

        if (empty($busquedas)) {
            $this->info('No hay servicios impagos con empresa y token configurados.');
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

        $this->info("Reconciliación finalizada: busquedas={$busquedasRealizadas}, pagos_procesados={$pagosProcesados}, errores={$errores}");

        return 0;
    }
}