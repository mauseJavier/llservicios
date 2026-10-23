<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Services\MercadoPago\MercadoPagoApiService;
use App\Services\MercadoPago\MercadoPagoDuplicatePaymentService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReembolsarDuplicadosMercadoPago extends Command
{
    protected $signature = 'mp:reembolsar-duplicados
        {--empresa= : ID de empresa o "all"}
        {--desde= : Fecha desde (Y-m-d). Por defecto 30 días atrás}
        {--hasta= : Fecha hasta (Y-m-d). Por defecto hoy}
        {--payment= : Payment ID puntual para reembolsar}
        {--apply : Ejecuta los reembolsos. Sin esta opción solo lista}
        {--force : No pide confirmación}
        {--motivo=Pago duplicado : Motivo del reembolso}';

    protected $description = 'Detecta y reembolsa pagos duplicados de MercadoPago por empresa';

    public function handle(): int
    {
        $empresaOpt = trim((string) $this->option('empresa'));

        if ($empresaOpt === '') {
            $this->error('Indicá --empresa=ID o --empresa=all.');

            return 1;
        }

        $desde = $this->option('desde')
            ? Carbon::parse((string) $this->option('desde'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $hasta = $this->option('hasta')
            ? Carbon::parse((string) $this->option('hasta'))->endOfDay()
            : now()->endOfDay();

        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $motivo = (string) ($this->option('motivo') ?: 'Pago duplicado');
        $paymentId = trim((string) $this->option('payment'));

        if ($paymentId !== '' && in_array(strtolower($empresaOpt), ['all', 'todas'], true)) {
            $this->error('Para --payment indicá una empresa específica con --empresa=ID.');

            return 1;
        }

        $empresas = $this->resolverEmpresas($empresaOpt);

        if ($empresas->isEmpty()) {
            $this->error('No se encontraron empresas con MP_ACCESS_TOKEN configurado.');

            return 1;
        }

        $service = app(MercadoPagoDuplicatePaymentService::class);

        if ($paymentId !== '') {
            return $this->reembolsarPagoPuntual(
                $empresas->first(),
                $paymentId,
                $apply,
                $force,
                $motivo,
                $service
            );
        }

        return $this->procesarDuplicados($empresas, $desde, $hasta, $apply, $force, $motivo, $service);
    }

    /**
     * @return Collection<int, Empresa>
     */
    private function resolverEmpresas(string $empresaOpt)
    {
        $valor = strtolower(trim($empresaOpt));

        if (in_array($valor, ['all', 'todas'], true)) {
            return Empresa::query()
                ->whereNotNull('MP_ACCESS_TOKEN')
                ->where('MP_ACCESS_TOKEN', '!=', '')
                ->orderBy('nombre')
                ->get();
        }

        if (! ctype_digit($valor)) {
            $this->error("Valor de --empresa inválido: '{$empresaOpt}'. Usá un ID numérico o 'all'.");

            return collect();
        }

        $empresa = Empresa::find((int) $valor);

        if (! $empresa) {
            $this->error("No existe la empresa con ID {$valor}.");

            return collect();
        }

        if (empty($empresa->MP_ACCESS_TOKEN)) {
            $this->error("La empresa {$empresa->id} no tiene MP_ACCESS_TOKEN configurado.");

            return collect();
        }

        return collect([$empresa]);
    }

    private function procesarDuplicados(
        $empresas,
        Carbon $desde,
        Carbon $hasta,
        bool $apply,
        bool $force,
        string $motivo,
        MercadoPagoDuplicatePaymentService $service
    ): int {
        $this->info('Buscando pagos duplicados desde '.$desde->toDateString().' hasta '.$hasta->toDateString().'...');

        $filas = [];
        $elegibles = [];
        $errores = 0;

        foreach ($empresas as $empresa) {
            try {
                $candidatos = $service->buscarDuplicados($empresa, $desde, $hasta);
            } catch (Throwable $e) {
                $this->error("Empresa {$empresa->id}: {$e->getMessage()}");
                $errores++;

                continue;
            }

            foreach ($candidatos as $candidato) {
                $idsDuplicados = collect($candidato['duplicates'])->pluck('id')->implode(', ');

                $filas[] = [
                    $empresa->id,
                    $candidato['referencia'],
                    $candidato['cliente'] ?? '-',
                    $candidato['servicio'] ?? '-',
                    $candidato['primary']['id'],
                    $idsDuplicados,
                    number_format((float) $candidato['monto_duplicado'], 2, ',', '.'),
                    $candidato['elegible'] ? 'Reembolsable' : 'Revisión manual',
                ];

                if ($candidato['elegible']) {
                    foreach ($candidato['duplicates'] as $duplicado) {
                        $elegibles[] = [
                            'empresa' => $empresa,
                            'payment_id' => (string) $duplicado['id'],
                            'monto' => (float) $duplicado['monto'],
                            'referencia' => $candidato['referencia'],
                        ];
                    }
                }
            }
        }

        if (empty($filas)) {
            $this->info('No se detectaron pagos duplicados.');

            return $errores > 0 ? 1 : 0;
        }

        $this->table(
            ['Empresa', 'Referencia', 'Cliente', 'Servicio', 'Conserva', 'Duplicados', 'Monto', 'Estado'],
            $filas
        );

        if (empty($elegibles)) {
            $this->warn('No hay pagos duplicados reembolsables automáticamente. Revisá los casos manuales.');

            return $errores > 0 ? 1 : 0;
        }

        $total = array_sum(array_column($elegibles, 'monto'));

        $this->info(sprintf(
            'Pagos reembolsables: %d — monto total: $%s',
            count($elegibles),
            number_format($total, 2, ',', '.')
        ));

        if (! $apply) {
            $this->info('Modo auditoría: no se reembolsó nada. Ejecutá con --apply para reembolsar.');

            return $errores > 0 ? 1 : 0;
        }

        if (! $force && ! $this->confirm('¿Reembolsar '.count($elegibles).' pago(s) por $'.number_format($total, 2, ',', '.').'?', false)) {
            $this->info('Operación cancelada.');

            return 0;
        }

        $reembolsados = 0;

        foreach ($elegibles as $item) {
            $resultado = $service->reembolsar(
                $item['empresa'],
                $item['payment_id'],
                $motivo,
                null
            );

            if ($resultado['success']) {
                $reembolsados++;
                $this->line("OK {$item['payment_id']} — refund {$resultado['refund_id']}");
            } else {
                $errores++;
                $this->error("ERROR {$item['payment_id']} — {$resultado['mensaje']}");
            }
        }

        $this->info("Reembolsos procesados: {$reembolsados}. Errores: {$errores}.");

        Log::info('MercadoPago - Reembolso de duplicados finalizado', [
            'empresas' => $empresas->pluck('id')->all(),
            'reembolsados' => $reembolsados,
            'errores' => $errores,
            'monto_total' => $total,
        ]);

        return $errores > 0 ? 1 : 0;
    }

    private function reembolsarPagoPuntual(
        Empresa $empresa,
        string $paymentId,
        bool $apply,
        bool $force,
        string $motivo,
        MercadoPagoDuplicatePaymentService $service
    ): int {
        $api = app(MercadoPagoApiService::class);
        $consulta = $api->getPayment($paymentId, (string) $empresa->MP_ACCESS_TOKEN);

        if (! ($consulta['success'] ?? false) || empty($consulta['data'])) {
            $this->error('No se pudo obtener el pago: '.($consulta['error'] ?? 'sin detalle'));

            return 1;
        }

        $pago = $consulta['data'];

        $this->table(
            ['Payment ID', 'Estado', 'Monto', 'Referencia', 'Empresa'],
            [[
                $paymentId,
                $pago['status'] ?? '-',
                number_format((float) ($pago['transaction_amount'] ?? 0), 2, ',', '.'),
                $pago['external_reference'] ?? '-',
                $empresa->id.' — '.$empresa->nombre,
            ]]
        );

        if (! $apply) {
            $this->info('Modo auditoría: no se reembolsó nada. Ejecutá con --apply para reembolsar.');

            return 0;
        }

        if (! $force && ! $this->confirm("¿Reembolsar el pago {$paymentId} de la empresa {$empresa->id}?", false)) {
            $this->info('Operación cancelada.');

            return 0;
        }

        $resultado = $service->reembolsar($empresa, $paymentId, $motivo, null);

        if (! $resultado['success']) {
            $this->error($resultado['mensaje']);

            return 1;
        }

        $this->info("Reembolso procesado. Refund ID: {$resultado['refund_id']}");

        return 0;
    }
}
