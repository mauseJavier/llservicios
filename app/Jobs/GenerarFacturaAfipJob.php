<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Pagos;
use App\Models\Empresa;
use App\Services\AfipService;

class GenerarFacturaAfipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;
    public $timeout = 120;

    protected int $pagoId;
    protected int $empresaId;
    protected int $puntoVenta;

    public function __construct(int $pagoId, int $empresaId, int $puntoVenta)
    {
        $this->onQueue('alta');
        $this->pagoId = $pagoId;
        $this->empresaId = $empresaId;
        $this->puntoVenta = $puntoVenta;
    }

    public function handle(): void
    {
        try {
            $pago = Pagos::with(['servicioPagar.cliente'])->findOrFail($this->pagoId);
            $empresa = Empresa::findOrFail($this->empresaId);

            if ($pago->tieneFacturaAfip()) {
                Log::info('GenerarFacturaAfipJob: el pago ya tiene factura AFIP', [
                    'pago_id' => $this->pagoId,
                ]);
                return;
            }

            $tipoComprobante = $this->determinarTipoComprobante($empresa, $pago->servicioPagar?->cliente);

            $afipService = new AfipService($empresa->id);
            $resultado = $afipService->crearFacturaDesdePago(
                $pago,
                $this->puntoVenta,
                $tipoComprobante,
                null,
                null
            );

            if ($resultado['success']) {
                $pago->update([
                    'afip_cae' => $resultado['cae'],
                    'afip_cae_vencimiento' => $resultado['cae_vencimiento'],
                    'afip_numero_comprobante' => $resultado['numero_comprobante'],
                    'afip_tipo_comprobante' => $tipoComprobante,
                    'afip_punto_venta' => $this->puntoVenta,
                ]);

                Log::info('Factura AFIP generada automáticamente', [
                    'pago_id' => $this->pagoId,
                    'cae' => $resultado['cae'],
                ]);
            } else {
                Log::error('GenerarFacturaAfipJob: error al generar factura', [
                    'pago_id' => $this->pagoId,
                    'error' => $resultado['error'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('GenerarFacturaAfipJob: error inesperado', [
                'pago_id' => $this->pagoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function determinarTipoComprobante(Empresa $empresa, $cliente): int
    {
        $condicionIvaEmisor = (int) ($empresa->condicion_iva_id ?? 0);
        $condicionIvaReceptor = (int) ($cliente->condicion_iva_id ?? config('afip.default_condicion_iva_receptor', 5));

        $emisoresMonotributo = [6, 13, 16];
        $receptoresRequierenA = [1, 6];

        if (in_array($condicionIvaEmisor, $emisoresMonotributo)) {
            return 11;
        }

        if ($condicionIvaEmisor === 1 && in_array($condicionIvaReceptor, $receptoresRequierenA)) {
            return 1;
        }

        if ($condicionIvaReceptor === 5) {
            return 6;
        }

        return 6;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerarFacturaAfipJob falló', [
            'pago_id' => $this->pagoId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
