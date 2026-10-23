<?php

namespace App\Services\MercadoPago;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MercadoPagoPagoIntento;
use App\Models\MercadoPagoReembolso;
use App\Models\Pagos;
use App\Models\ServicioPagar;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Detecta pagos duplicados de MercadoPago por referencia externa y ejecuta
 * reembolsos seguros desde el sistema.
 *
 * Reglas de seguridad de la Fase 1:
 *  - Solo se reembolsan pagos que pertenecen a la empresa seleccionada.
 *  - Solo se reembolsan pagos aprobados y no aplicados a un servicio.
 *  - Si el pago está aplicado o tiene factura AFIP, se deriva a revisión manual.
 *  - Los pagos históricos anteriores a la creación de la deuda se ignoran.
 */
class MercadoPagoDuplicatePaymentService
{
    private const LIMITE_PAGOS_POR_PAGINA = 50;

    private const MAX_PAGINAS = 20;

    public function __construct(private MercadoPagoApiService $api) {}

    /**
     * Buscar candidatos a reembolso para una empresa en un rango de fechas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buscarDuplicados(Empresa $empresa, Carbon $desde, Carbon $hasta): array
    {
        $token = (string) ($empresa->MP_ACCESS_TOKEN ?? '');

        if ($token === '') {
            throw new RuntimeException('La empresa no tiene MP_ACCESS_TOKEN configurado.');
        }

        $pagos = $this->obtenerPagosAprobados($token, $desde, $hasta);

        if (empty($pagos)) {
            return [];
        }

        $ids = collect($pagos)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values()
            ->all();

        $reembolsados = MercadoPagoReembolso::query()
            ->whereIn('payment_id', $ids)
            ->whereIn('estado', ['aprobado', 'pendiente'])
            ->pluck('payment_id')
            ->flip();

        $porReferencia = [];

        foreach ($pagos as $pago) {
            $referencia = trim((string) ($pago['external_reference'] ?? ''));
            $paymentId = (string) ($pago['id'] ?? '');

            if ($referencia === '' || $paymentId === '') {
                continue;
            }

            if ($reembolsados->has($paymentId)) {
                continue;
            }

            if (($pago['status'] ?? null) !== 'approved') {
                continue;
            }

            $monto = (float) ($pago['transaction_amount'] ?? 0);
            $reembolsado = (float) ($pago['transaction_amount_refunded'] ?? 0);

            if ($monto > 0 && $reembolsado >= $monto) {
                continue;
            }

            $porReferencia[$referencia][] = $pago;
        }

        $candidatos = [];

        foreach ($porReferencia as $referencia => $pagosReferencia) {
            $contexto = $this->resolverReferencia($referencia, $empresa);

            if (! $contexto) {
                continue;
            }

            $pagosReferencia = array_values(array_filter(
                $pagosReferencia,
                fn (array $pago) => ! $this->esPagoHistorico($pago, $contexto)
            ));

            if (count($pagosReferencia) < 2) {
                continue;
            }

            $aplicados = $this->idsAplicados($contexto);
            $primario = $this->elegirPrimario($pagosReferencia, $aplicados);
            $duplicados = array_values(array_filter(
                $pagosReferencia,
                fn (array $pago) => (string) $pago['id'] !== (string) $primario['id']
            ));

            if (empty($duplicados)) {
                continue;
            }

            $motivos = $this->motivosNoElegible($contexto, $primario, $duplicados, $aplicados);

            $candidatos[] = [
                'referencia' => $referencia,
                'empresa_id' => $empresa->id,
                'tipo' => $contexto['tipo'],
                'cliente' => $contexto['cliente'],
                'servicio' => $contexto['servicio'],
                'servicio_pagar_id' => $contexto['servicio_pagar_id'],
                'primary' => $this->normalizarPago($primario, $aplicados),
                'duplicates' => array_map(
                    fn (array $pago) => $this->normalizarPago($pago, $aplicados),
                    $duplicados
                ),
                'monto_duplicado' => array_sum(array_map(
                    fn (array $pago) => (float) ($pago['transaction_amount'] ?? 0),
                    $duplicados
                )),
                'elegible' => empty($motivos),
                'motivos' => $motivos,
            ];
        }

        usort($candidatos, function (array $a, array $b) {
            if ($a['elegible'] !== $b['elegible']) {
                return $a['elegible'] ? -1 : 1;
            }

            return strcmp(
                (string) ($b['primary']['fecha'] ?? ''),
                (string) ($a['primary']['fecha'] ?? '')
            );
        });

        return $candidatos;
    }

    /**
     * Reembolsar un pago validando pertenencia, estado y aplicación local.
     *
     * @return array{success: bool, payment_id: ?string, refund_id: ?string, estado: string, monto: ?float, status_pago: ?string, error: ?string, mensaje: string}
     */
    public function reembolsar(Empresa $empresa, string $paymentId, string $motivo, ?User $user = null): array
    {
        $token = (string) ($empresa->MP_ACCESS_TOKEN ?? '');

        if ($token === '') {
            return $this->error('La empresa no tiene MP_ACCESS_TOKEN configurado.');
        }

        $lock = Cache::lock('mp_reembolso_'.$paymentId, 120);

        if (! $lock->get()) {
            return $this->error('Ya hay un reembolso en proceso para este pago.');
        }

        try {
            $existente = MercadoPagoReembolso::where('payment_id', $paymentId)->first();

            if ($existente && in_array($existente->estado, ['aprobado', 'pendiente'], true)) {
                return $this->error('Este pago ya tiene un reembolso registrado ('.$existente->estado.').');
            }

            $consulta = $this->api->getPayment($paymentId, $token);

            if (! ($consulta['success'] ?? false) || empty($consulta['data'])) {
                return $this->error('No se pudo obtener el pago desde MercadoPago: '.($consulta['error'] ?? 'sin detalle'));
            }

            $pago = $consulta['data'];
            $referencia = trim((string) ($pago['external_reference'] ?? ''));
            $contexto = $this->resolverReferencia($referencia, $empresa);

            if (! $contexto) {
                return $this->error('El pago no pertenece a la empresa seleccionada o la referencia no es reconocida.');
            }

            if (($pago['status'] ?? null) !== 'approved') {
                return $this->error('El pago no está aprobado (estado: '.($pago['status'] ?? 'desconocido').').');
            }

            $monto = (float) ($pago['transaction_amount'] ?? 0);
            $reembolsado = (float) ($pago['transaction_amount_refunded'] ?? 0);

            if ($monto <= 0) {
                return $this->error('El pago no tiene un monto válido.');
            }

            if ($reembolsado >= $monto) {
                return $this->error('El pago ya está reembolsado.');
            }

            if ($this->pagoAplicado($paymentId)) {
                $pagoLocal = Pagos::where('mp_payment_id', $paymentId)->first();

                if ($pagoLocal && ! empty($pagoLocal->afip_cae)) {
                    return $this->error('El pago está aplicado y tiene factura AFIP. Emití una nota de crédito antes de reembolsar.');
                }

                return $this->error('El pago está aplicado a un servicio. Para reembolsarlo hay que revertir la aplicación local primero (revisión manual).');
            }

            $registro = MercadoPagoReembolso::updateOrCreate(
                ['payment_id' => $paymentId],
                [
                    'external_reference' => $referencia !== '' ? $referencia : null,
                    'empresa_id' => $empresa->id,
                    'monto' => $monto,
                    'motivo' => $motivo,
                    'estado' => 'pendiente',
                    'mp_refund_id' => null,
                    'respuesta' => null,
                    'user_id' => $user?->id,
                ]
            );

            $resultado = $this->api->refundPayment($paymentId, null, $token);
            $estado = $resultado['success'] ? 'aprobado' : 'error';

            $registro->update([
                'estado' => $estado,
                'mp_refund_id' => $resultado['refund_id'] ?? null,
                'respuesta' => $resultado['data'] ?? ['error' => $resultado['error'] ?? null],
            ]);

            Log::info('MercadoPago - Reembolso ejecutado', [
                'payment_id' => $paymentId,
                'empresa_id' => $empresa->id,
                'user_id' => $user?->id,
                'estado' => $estado,
                'refund_id' => $resultado['refund_id'] ?? null,
                'error' => $resultado['error'] ?? null,
            ]);

            if (! $resultado['success']) {
                return $this->error('MercadoPago rechazó el reembolso: '.($resultado['error'] ?? 'sin detalle'));
            }

            $actualizado = $this->api->getPayment($paymentId, $token);
            $pagoFinal = $actualizado['data'] ?? [];

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'refund_id' => $resultado['refund_id'] ?? null,
                'estado' => $estado,
                'monto' => $monto,
                'status_pago' => $pagoFinal['status'] ?? null,
                'error' => null,
                'mensaje' => 'Reembolso solicitado correctamente.',
            ];
        } catch (Throwable $e) {
            Log::error('MercadoPago - Error reembolsando pago', [
                'payment_id' => $paymentId,
                'empresa_id' => $empresa->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error inesperado: '.$e->getMessage());
        } finally {
            $lock->release();
        }
    }

    /**
     * Obtener todos los pagos aprobados de la cuenta en el rango de fechas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function obtenerPagosAprobados(string $token, Carbon $desde, Carbon $hasta): array
    {
        $pagos = [];
        $offset = 0;

        for ($pagina = 0; $pagina < self::MAX_PAGINAS; $pagina++) {
            $resultado = $this->api->searchPayments([
                'range' => 'date_created',
                'begin_date' => $desde->copy()->startOfDay()->format('Y-m-d\TH:i:s.vP'),
                'end_date' => $hasta->copy()->endOfDay()->format('Y-m-d\TH:i:s.vP'),
                'status' => 'approved',
                'sort' => 'date_created',
                'criteria' => 'desc',
                'limit' => self::LIMITE_PAGOS_POR_PAGINA,
                'offset' => $offset,
            ], $token);

            if (! ($resultado['success'] ?? false)) {
                throw new RuntimeException($resultado['error'] ?? 'Error consultando pagos en MercadoPago.');
            }

            $results = $resultado['results'] ?? [];

            $pagos = array_merge($pagos, $results);

            if (count($results) < self::LIMITE_PAGOS_POR_PAGINA) {
                break;
            }

            $offset += self::LIMITE_PAGOS_POR_PAGINA;
        }

        return $pagos;
    }

    /**
     * Resolver una referencia externa y validar que pertenezca a la empresa.
     *
     * @return array<string, mixed>|null
     */
    private function resolverReferencia(string $referencia, Empresa $empresa): ?array
    {
        if (preg_match('/^servicio_pagar_(\d+)$/', $referencia, $matches)) {
            $servicioPagar = ServicioPagar::with(['cliente', 'servicio.empresa', 'pago'])
                ->find((int) $matches[1]);

            if (! $servicioPagar || (int) ($servicioPagar->servicio?->empresa_id ?? 0) !== (int) $empresa->id) {
                return null;
            }

            return [
                'tipo' => 'servicio',
                'empresa_id' => (int) $empresa->id,
                'cliente' => $servicioPagar->cliente?->nombre,
                'servicio' => $servicioPagar->servicio?->nombre,
                'servicio_pagar_id' => $servicioPagar->id,
                'servicio_pagar' => $servicioPagar,
                'intento' => null,
                'created_at' => $servicioPagar->created_at,
            ];
        }

        if (preg_match('/^lote_([A-Za-z0-9\-]+)$/', $referencia, $matches)) {
            $intento = MercadoPagoPagoIntento::with(['empresa', 'cliente', 'serviciosPagar.servicio'])
                ->where('token', $matches[1])
                ->first();

            if (! $intento || (int) $intento->empresa_id !== (int) $empresa->id) {
                return null;
            }

            return [
                'tipo' => 'intento',
                'empresa_id' => (int) $empresa->id,
                'cliente' => $intento->cliente->nombre ?? null,
                'servicio' => $intento->serviciosPagar
                    ->pluck('servicio.nombre')
                    ->filter()
                    ->unique()
                    ->implode(', ') ?: null,
                'servicio_pagar_id' => null,
                'servicio_pagar' => null,
                'intento' => $intento,
                'created_at' => $intento->created_at,
            ];
        }

        if (preg_match('/^cliente_impagos_(\d+)_(\d+)$/', $referencia, $matches)) {
            $clienteId = (int) $matches[1];
            $empresaId = (int) $matches[2];

            if ($empresaId !== (int) $empresa->id) {
                return null;
            }

            $cliente = Cliente::find($clienteId);

            return [
                'tipo' => 'cliente',
                'empresa_id' => $empresaId,
                'cliente' => $cliente->nombre ?? null,
                'servicio' => null,
                'servicio_pagar_id' => null,
                'servicio_pagar' => null,
                'intento' => null,
                'created_at' => null,
            ];
        }

        return null;
    }

    /**
     * Ignorar pagos anteriores a la creación de la deuda. Evita que una
     * referencia reutilizada después de un reset de base levante pagos viejos.
     *
     * @param  array<string, mixed>  $pago
     * @param  array<string, mixed>  $contexto
     */
    private function esPagoHistorico(array $pago, array $contexto): bool
    {
        $fechaPago = $pago['date_approved'] ?? $pago['date_created'] ?? null;
        $creacion = $contexto['created_at'] ?? null;

        if (! $fechaPago || ! $creacion) {
            return false;
        }

        try {
            return Carbon::parse($fechaPago)->lt(Carbon::parse($creacion));
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * IDs de pagos ya aplicados localmente al servicio o intento.
     *
     * @param  array<string, mixed>  $contexto
     * @return string[]
     */
    private function idsAplicados(array $contexto): array
    {
        if ($contexto['tipo'] === 'servicio' && $contexto['servicio_pagar']) {
            $servicioPagar = $contexto['servicio_pagar'];
            $ids = [];

            if (! empty($servicioPagar->mp_payment_id)) {
                $ids[] = (string) $servicioPagar->mp_payment_id;
            }

            if (! empty($servicioPagar->pago?->mp_payment_id)) {
                $ids[] = (string) $servicioPagar->pago->mp_payment_id;
            }

            return array_values(array_unique($ids));
        }

        if ($contexto['tipo'] === 'intento' && $contexto['intento']) {
            $idsServicios = $contexto['intento']->serviciosPagar->pluck('id')->all();

            $ids = array_merge(
                ServicioPagar::whereIn('id', $idsServicios)
                    ->whereNotNull('mp_payment_id')
                    ->pluck('mp_payment_id')
                    ->all(),
                Pagos::whereIn('id_servicio_pagar', $idsServicios)
                    ->whereNotNull('mp_payment_id')
                    ->pluck('mp_payment_id')
                    ->all()
            );

            return array_values(array_unique(array_map('strval', $ids)));
        }

        return [];
    }

    /**
     * Elegir el pago que se conserva: el aplicado localmente o, si ninguno
     * está aplicado, el más antiguo.
     *
     * @param  array<int, array<string, mixed>>  $pagos
     * @param  string[]  $aplicados
     * @return array<string, mixed>
     */
    private function elegirPrimario(array $pagos, array $aplicados): array
    {
        $aplicadosSet = array_flip($aplicados);

        foreach ($pagos as $pago) {
            if (isset($aplicadosSet[(string) $pago['id']])) {
                return $pago;
            }
        }

        usort($pagos, function (array $a, array $b) {
            return strcmp(
                (string) ($a['date_approved'] ?? $a['date_created'] ?? ''),
                (string) ($b['date_approved'] ?? $b['date_created'] ?? '')
            );
        });

        return $pagos[0];
    }

    /**
     * Motivos por los que un candidato no se puede reembolsar automáticamente.
     *
     * @param  array<string, mixed>  $contexto
     * @param  array<string, mixed>  $primario
     * @param  array<int, array<string, mixed>>  $duplicados
     * @param  string[]  $aplicados
     * @return string[]
     */
    private function motivosNoElegible(array $contexto, array $primario, array $duplicados, array $aplicados): array
    {
        $motivos = [];

        if ($contexto['tipo'] !== 'servicio') {
            $motivos[] = 'Referencia agrupada; revisar manualmente';
        }

        $aplicadosSet = array_flip($aplicados);
        $montoPrimario = (float) ($primario['transaction_amount'] ?? 0);
        $monedaPrimario = (string) ($primario['currency_id'] ?? '');

        foreach ($duplicados as $duplicado) {
            $paymentId = (string) ($duplicado['id'] ?? '');

            if (isset($aplicadosSet[$paymentId])) {
                $motivos[] = 'El pago duplicado está aplicado a un servicio';
            }

            if (
                (float) ($duplicado['transaction_amount'] ?? 0) !== $montoPrimario
                || (string) ($duplicado['currency_id'] ?? '') !== $monedaPrimario
            ) {
                $motivos[] = 'Monto o moneda distinta al pago principal';
            }

            if ((float) ($duplicado['transaction_amount_refunded'] ?? 0) > 0) {
                $motivos[] = 'El pago duplicado tiene reembolsos previos';
            }
        }

        return array_values(array_unique($motivos));
    }

    /**
     * @param  array<string, mixed>  $pago
     * @param  string[]  $aplicados
     * @return array<string, mixed>
     */
    private function normalizarPago(array $pago, array $aplicados): array
    {
        $paymentId = (string) ($pago['id'] ?? '');

        return [
            'id' => $paymentId,
            'status' => $pago['status'] ?? null,
            'monto' => (float) ($pago['transaction_amount'] ?? 0),
            'moneda' => (string) ($pago['currency_id'] ?? ''),
            'fecha' => $pago['date_approved'] ?? $pago['date_created'] ?? null,
            'reembolsado' => (float) ($pago['transaction_amount_refunded'] ?? 0),
            'aplicado' => in_array($paymentId, $aplicados, true),
            'payer' => $pago['payer']['email'] ?? null,
            'payment_method' => $pago['payment_method_id'] ?? null,
        ];
    }

    private function pagoAplicado(string $paymentId): bool
    {
        return ServicioPagar::where('mp_payment_id', $paymentId)->exists()
            || Pagos::where('mp_payment_id', $paymentId)->exists();
    }

    /**
     * @return array{success: bool, payment_id: ?string, refund_id: ?string, estado: string, monto: ?float, status_pago: ?string, error: ?string, mensaje: string}
     */
    private function error(string $mensaje): array
    {
        return [
            'success' => false,
            'payment_id' => null,
            'refund_id' => null,
            'estado' => 'error',
            'monto' => null,
            'status_pago' => null,
            'error' => $mensaje,
            'mensaje' => $mensaje,
        ];
    }
}
