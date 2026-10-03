<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\Pagos;
use App\Models\ServicioPagar;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuditarPagosCruzadosMercadoPago extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mp:auditar-pagos-cruzados
        {--empresa= : ID de empresa, o "all"/"todas" para auditar todas}
        {--apply : Revierte los pagos cruzados detectados}';

    /**
     * @var string
     */
    protected $description = 'Detecta (y opcionalmente revierte) deudas pagadas con un pago de MercadoPago anterior al período de la deuda';

    private const BASE_URL_MP = 'https://api.mercadopago.com';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $empresaFiltro = $this->option('empresa');

        // El filtro por empresa es obligatorio para evitar correr (y revertir)
        // sobre todo el sistema por accidente.
        if ($empresaFiltro === null || trim((string) $empresaFiltro) === '') {
            $this->error('Indicá --empresa=ID o --empresa=all (todas).');

            return 1;
        }

        $valor = strtolower(trim((string) $empresaFiltro));
        $todas = in_array($valor, ['all', 'todas'], true);
        $empresa = null;
        $alcance = 'todas las empresas';

        if (! $todas) {
            if (! ctype_digit($valor)) {
                $this->error("Valor de --empresa inválido: '{$empresaFiltro}'. Usá un ID numérico o 'all'.");

                return 1;
            }

            $empresa = Empresa::find((int) $valor);

            if (! $empresa) {
                $this->error("No existe la empresa con ID {$valor}.");

                return 1;
            }

            $alcance = "empresa {$empresa->nombre} (#{$empresa->id})";
        }

        $consulta = ServicioPagar::with(['pago', 'cliente', 'servicio.empresa'])
            ->where('estado', 'pago')
            ->whereNotNull('mp_payment_id');

        if (! $todas) {
            $consulta->whereHas('servicio', fn ($q) => $q->where('empresa_id', $empresa->id));
        }

        $servicios = $consulta->get();

        if ($servicios->isEmpty()) {
            $this->info("No hay deudas pagas con pago de MercadoPago para auditar ({$alcance}).");

            return 0;
        }

        $cachePagos = [];
        $anomalias = [];

        foreach ($servicios as $servicioPagar) {
            $empresa = $servicioPagar->servicio->empresa ?? null;
            $paymentId = (string) $servicioPagar->mp_payment_id;

            if (! $empresa || empty($empresa->MP_ACCESS_TOKEN) || $paymentId === '') {
                continue;
            }

            if (! array_key_exists($paymentId, $cachePagos)) {
                $cachePagos[$paymentId] = $this->obtenerPago(
                    $paymentId,
                    (string) $empresa->MP_ACCESS_TOKEN
                );
            }

            $pagoMp = $cachePagos[$paymentId];

            if (! $pagoMp) {
                continue;
            }

            $fechaPago = $pagoMp['date_approved'] ?? $pagoMp['date_created'] ?? null;

            if (! $fechaPago) {
                continue;
            }

            $periodo = $servicioPagar->periodo_servicio ?? $servicioPagar->created_at;

            if (! $periodo) {
                continue;
            }

            $fechaPagoCarbon = Carbon::parse($fechaPago);
            $periodoCarbon = Carbon::parse($periodo)->startOfMonth();

            // Anomalía: el pago es anterior al inicio del período de la deuda.
            // No puede pagarse una deuda antes de que exista.
            if ($fechaPagoCarbon->lt($periodoCarbon)) {
                $anomalias[] = [
                    'servicio_pagar' => $servicioPagar,
                    'payment_id' => $paymentId,
                    'fecha_pago' => $fechaPagoCarbon,
                    'periodo' => $periodoCarbon,
                    'external_reference' => $pagoMp['external_reference'] ?? null,
                    'monto' => (float) ($pagoMp['transaction_amount'] ?? 0),
                ];
            }
        }

        if (empty($anomalias)) {
            $this->info('No se detectaron pagos cruzados.');

            return 0;
        }

        $this->warn(sprintf('Se detectaron %d deudas posiblemente pagadas con un pago anterior a su período (%s):', count($anomalias), $alcance));

        $this->table(
            ['servicio_pagar', 'empresa', 'cliente', 'período', 'payment_id', 'fecha pago', 'ref', 'monto', 'afip'],
            array_map(function (array $a) {
                $pagoLocal = $a['servicio_pagar']->pago;
                $tieneAfip = $pagoLocal && ! empty($pagoLocal->afip_cae);
                $empresa = $a['servicio_pagar']->servicio->empresa ?? null;

                return [
                    $a['servicio_pagar']->id,
                    $empresa ? $empresa->nombre.' (#'.$empresa->id.')' : '-',
                    $a['servicio_pagar']->cliente->nombre ?? '-',
                    $a['periodo']->format('Y-m'),
                    $a['payment_id'],
                    $a['fecha_pago']->format('Y-m-d'),
                    $a['external_reference'] ?? '-',
                    number_format($a['monto'], 2, ',', '.'),
                    $tieneAfip ? 'sí' : 'no',
                ];
            }, $anomalias)
        );

        if (! $apply) {
            $this->info('Modo auditoría (sin cambios). Ejecutá con --apply para revertirlos.');

            return 0;
        }

        if (! $this->confirm("¿Revertir estas deudas a impago y eliminar su pago asociado ({$alcance})?", false)) {
            $this->info('Operación cancelada.');

            return 0;
        }

        $revertidos = 0;
        $omitidos = 0;

        foreach ($anomalias as $a) {
            $servicioPagar = $a['servicio_pagar'];
            $pagoLocal = $servicioPagar->pago;

            if ($pagoLocal && ! empty($pagoLocal->afip_cae)) {
                $this->warn("Omitido servicio_pagar {$servicioPagar->id}: tiene factura AFIP asociada.");
                $omitidos++;

                continue;
            }

            DB::transaction(function () use ($servicioPagar, $a) {
                $servicioPagar->update([
                    'estado' => 'impago',
                    'mp_payment_id' => null,
                ]);

                Pagos::where('id_servicio_pagar', $servicioPagar->id)
                    ->where('mp_payment_id', $a['payment_id'])
                    ->delete();
            });

            Log::warning('Remediación de pago cruzado MercadoPago', [
                'servicio_pagar_id' => $servicioPagar->id,
                'payment_id' => $a['payment_id'],
                'fecha_pago' => $a['fecha_pago']->toDateTimeString(),
                'periodo' => $a['periodo']->toDateString(),
            ]);

            $revertidos++;
        }

        $this->info("Remediación finalizada: revertidos={$revertidos}, omitidos={$omitidos}");

        return 0;
    }

    private function obtenerPago(string $paymentId, string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->get(self::BASE_URL_MP.'/v1/payments/'.$paymentId);

            if (! $response->successful()) {
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('No se pudo obtener el pago MP en auditoría', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
