<?php

namespace App\Http\Controllers\MercadoPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Empresa;
use App\Models\ServicioPagar;
use App\Models\Pagos;
use App\Jobs\ProcesarPagoJob;
use App\Services\MercadoPago\MercadoPagoApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MercadoPagoWebhookController extends Controller
{
    private const USUARIO_PAGO_ONLINE_EMAIL = 'pago.online@example.com';

    /**
     * Resultado: pago procesado correctamente.
     */
    private const RESULTADO_PROCESADO = 'procesado';

    /**
     * Resultado: error transitorio; el webhook debe responder 500 para que MP reintente.
     */
    private const RESULTADO_RETRY = 'retry';

    /**
     * Resultado: notificación que no se puede procesar (pago ajeno, ya procesado,
     * o tipo no soportado). Se responde 200 para no generar reintentos infinitos.
     */
    private const RESULTADO_IGNORADO = 'ignorado';

    private const BASE_URL_MP = 'https://api.mercadopago.com';

    private function resolverUsuarioSistemaId(): int
    {
        $idUsuarioPago = \App\Models\User::where('email', self::USUARIO_PAGO_ONLINE_EMAIL)->value('id');
        return $idUsuarioPago ? (int) $idUsuarioPago : 0;
    }

    /**
     * Manejar notificaciones de webhook de MercadoPago
     */
    public function handleNotification(Request $request)
    {
        try {
            // Log de la notificación recibida
            Log::info('Webhook MercadoPago recibido', [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            $this->logConfiguracionEntorno('webhook_entrante');

            // Obtener datos del webhook
            // MercadoPago envía dos formatos: "WebHook v1.0" (campo type + data.id)
            // y "Feed v2.0" (campo topic + id al nivel raíz). Se soportan ambos.
            $data = $request->input('data', []);
            $tipo = $request->input('type') ?? $request->input('topic');
            $empresaId = $request->input('empresa_id');
            $paymentId = null;
            $merchantOrderId = null;

            // Solo procesar notificaciones de pago y de merchant order
            if ($tipo === 'payment') {
                $paymentId = $data['id'] ?? $request->input('id');
                $userId = (string) ($request->input('user_id') ?? $request->input('userId') ?? '');

                if (!$paymentId) {
                    Log::warning('Webhook sin payment ID');
                    return response()->json(['error' => 'Payment ID missing'], 400);
                }

                $resultado = $this->procesarPagoPorId($paymentId, [
                    'source' => 'webhook_payment',
                    'user_id' => $userId,
                    'empresa_id' => $empresaId,
                ]);

                return $this->respuestaWebhook($resultado);
            }

            if ($tipo === 'merchant_order') {
                $merchantOrderId = $data['id'] ?? $request->input('id');
                $userId = (string) ($request->input('user_id') ?? $request->input('userId') ?? '');

                if (!$merchantOrderId) {
                    Log::warning('Webhook sin merchant order ID');
                    return response()->json(['error' => 'Merchant order ID missing'], 400);
                }

                $resultado = $this->procesarMerchantOrder($merchantOrderId, [
                    'source' => 'webhook_merchant_order',
                    'user_id' => $userId,
                    'empresa_id' => $empresaId,
                ]);

                return $this->respuestaWebhook($resultado);
            }

            Log::info('Webhook ignorado - tipo no soportado', ['type' => $tipo]);
            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook MercadoPago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Traducir el resultado de procesamiento a la respuesta HTTP esperada por
     * MercadoPago: 200 si se resolvió (para no reintentar) o 500 si es un error
     * transitorio (MP reintentará la notificación más adelante).
     */
    private function respuestaWebhook(string $resultado): JsonResponse
    {
        if ($resultado === self::RESULTADO_RETRY) {
            return response()->json(['error' => 'Procesamiento pendiente, reintentar'], 500);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Procesamiento reutilizable desde callbacks de retorno (success/pending/failure)
     * para cubrir escenarios en donde el webhook se retrasa o no llega.
     */
    public function procesarPagoDesdeRetorno(string $paymentId, ?string $externalReference = null): bool
    {
        return $this->procesarPagoPorId($paymentId, [
            'source' => 'back_url',
            'external_reference' => $externalReference,
        ]) === self::RESULTADO_PROCESADO;
    }

    /**
     * Procesar un pago de MercadoPago a partir de su ID: obtiene el pago,
     * resuelve los servicios asociados, distribuye importes y actualiza/notifica.
     *
     * @return string uno de RESULTADO_PROCESADO / RESULTADO_RETRY / RESULTADO_IGNORADO
     */
    private function procesarPagoPorId(string $paymentId, array $contexto = []): string
    {
        $lockKey = 'mp_webhook_payment_lock_' . $paymentId;
        $lockTtl = 120;

        if (!Cache::add($lockKey, 1, $lockTtl)) {
            Log::info('Webhook pago omitido por lock activo (posible duplicado concurrente)', [
                'event' => 'mp_webhook_payment_lock_skip',
                'payment_id' => $paymentId,
                'contexto' => $contexto,
            ]);

            return self::RESULTADO_IGNORADO;
        }

        try {
            $serviciosPagar = collect();

            // Resolver los servicios por referencia externa cuando viene del retorno
            // del navegador o de la reconciliación.
            if (!empty($contexto['external_reference'])) {
                $serviciosPagar = $this->resolverServiciosPorReferencia((string) $contexto['external_reference']);
            }

            $accessToken = $this->resolverAccessToken($contexto, $serviciosPagar);

            if (empty($accessToken)) {
                Log::error('MercadoPago webhook sin token resoluble para procesar pago', [
                    'event' => 'mp_webhook_token_resolution_error',
                    'payment_id' => $paymentId,
                    'contexto' => $contexto,
                    'token_por_empresa_id' => !empty($contexto['empresa_id']),
                    'token_por_referencia' => !$serviciosPagar->isEmpty(),
                    'token_por_user_id' => !empty($contexto['user_id']),
                    'token_global_configurado' => !empty(config('services.mercadopago.access_token')),
                ]);
                $this->logConfiguracionEntorno('token_resolution_error_payment', ['payment_id' => $paymentId]);

                return self::RESULTADO_RETRY;
            }

            // Obtener el pago. Si falla con el token inicial, se reintenta con el
            // token de cada empresa (cubre preferencias viejas sin empresa_id y
            // webhooks sin user_id resoluble).
            $payment = $this->obtenerPagoReintentando($paymentId, $accessToken);

            if (!$payment) {
                Log::error('Error obteniendo payment en webhook/retorno', [
                    'event' => 'mp_webhook_payment_fetch_error',
                    'payment_id' => $paymentId,
                    'contexto' => $contexto,
                ]);

                return self::RESULTADO_RETRY;
            }

            Log::info('Pago obtenido via webhook', [
                'payment_id' => $paymentId,
                'status' => $payment->status ?? null,
                'external_reference' => $payment->external_reference ?? null
            ]);

            // Buscar los servicios asociados por referencia externa
            if ($serviciosPagar->isEmpty()) {
                $serviciosPagar = $this->resolverServiciosPorReferencia($payment->external_reference ?? '');
            }

            if ($serviciosPagar->isEmpty()) {
                Log::warning('Webhook sin servicios asociados', [
                    'payment_id' => $paymentId,
                    'external_reference' => $payment->external_reference ?? null
                ]);

                return self::RESULTADO_IGNORADO;
            }

            // Multi-tenant: reconsultar el pago con el token de la empresa que posee
            // el servicio si el token usado difiere (best effort).
            $empresa = $serviciosPagar->first()->servicio->empresa ?? null;
            $empresaToken = $empresa->MP_ACCESS_TOKEN ?? null;

            if (!empty($empresaToken) && $empresaToken !== $accessToken) {
                $paymentReconsultado = $this->obtenerPagoConToken($paymentId, (string) $empresaToken);

                if ($paymentReconsultado) {
                    $payment = $paymentReconsultado;
                } else {
                    Log::warning('No se pudo reconsultar payment con token de empresa', [
                        'payment_id' => $paymentId,
                        'empresa_id' => $empresa->id ?? null,
                    ]);
                }
            }

            $esAgregado = $serviciosPagar->count() > 1;
            $importesPorServicio = [];

            if ($esAgregado) {
                $montoNeto = (float) ($payment->transaction_details->net_received_amount ?? $payment->transaction_amount ?? 0);
                $importesPorServicio = $this->distribuirImportes($serviciosPagar, $montoNeto);
            }

            foreach ($serviciosPagar as $servicioPagar) {
                $this->processPaymentNotification($servicioPagar, $payment, [
                    'importe' => $esAgregado ? ($importesPorServicio[$servicioPagar->id] ?? null) : null,
                ]);
            }

            return self::RESULTADO_PROCESADO;
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Resolver el access token a usar para consultar un pago, en orden de prioridad:
     * 1. Token de la empresa indicada por ?empresa_id= en la notification_url.
     * 2. Token de la empresa derivada de la external_reference (retorno/reconciliación).
     * 3. Token de la empresa por user_id del payload del webhook.
     * 4. Token global configurado en el .env.
     */
    private function resolverAccessToken(array $contexto, $serviciosPagar): ?string
    {
        if (!empty($contexto['empresa_id'])) {
            $empresa = Empresa::find((int) $contexto['empresa_id']);

            if ($empresa && !empty($empresa->MP_ACCESS_TOKEN)) {
                return (string) $empresa->MP_ACCESS_TOKEN;
            }
        }

        if (!$serviciosPagar->isEmpty()) {
            $tokenReferencia = $this->resolverTokenDesdeServicios($serviciosPagar);

            if (!empty($tokenReferencia)) {
                return (string) $tokenReferencia;
            }
        }

        $tokenPorUserId = $this->resolverTokenEmpresaPorUserId($contexto['user_id'] ?? null);

        if (!empty($tokenPorUserId)) {
            return (string) $tokenPorUserId;
        }

        $tokenGlobal = (string) config('services.mercadopago.access_token');

        return $tokenGlobal !== '' ? $tokenGlobal : null;
    }

    /**
     * Intentar obtener el pago con el token dado y, si falla, con el token de cada
     * empresa que tenga credenciales configuradas. Esto hace que el webhook funcione
     * aunque el payload no incluya user_id ni la notification_url tenga empresa_id.
     */
    private function obtenerPagoReintentando(string $paymentId, string $accessToken): ?object
    {
        $payment = $this->obtenerPagoConToken($paymentId, $accessToken);

        if ($payment) {
            return $payment;
        }

        $empresas = Empresa::query()
            ->whereNotNull('MP_ACCESS_TOKEN')
            ->where('MP_ACCESS_TOKEN', '!=', '')
            ->get(['MP_ACCESS_TOKEN']);

        foreach ($empresas as $empresa) {
            $token = (string) $empresa->MP_ACCESS_TOKEN;

            if ($token === $accessToken) {
                continue;
            }

            $payment = $this->obtenerPagoConToken($paymentId, $token);

            if ($payment) {
                Log::info('Pago obtenido con token de empresa alternativo', [
                    'payment_id' => $paymentId,
                    'empresa_id' => $empresa->id ?? null,
                ]);

                return $payment;
            }
        }

        return null;
    }

    /**
     * Obtener un pago de MercadoPago vía API con un token específico.
     * Devuelve el pago como objeto stdClass o null si no se pudo obtener.
     */
    private function obtenerPagoConToken(string $paymentId, string $accessToken): ?object
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get(self::BASE_URL_MP . '/v1/payments/' . $paymentId);

            if (!$response->successful()) {
                Log::info('Fallo al obtener payment con token', [
                    'payment_id' => $paymentId,
                    'status_code' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return json_decode($response->body());
        } catch (\Throwable $e) {
            Log::info('Excepción al obtener payment con token', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Procesar una merchant order de MercadoPago: obtiene la orden vía API y
     * procesa cada uno de sus pagos aprobados. Cubre el caso en que MercadoPago
     * solo entrega el webhook de tipo merchant_order (best effort).
     *
     * Si la cuenta no tiene habilitado el API de merchant orders (por ejemplo
     * respuesta 403 de PolicyAgent), se registra el evento y se responde 200:
     * el webhook de tipo "payment" sigue siendo la vía confiable de confirmación.
     *
     * @return string uno de RESULTADO_PROCESADO / RESULTADO_RETRY / RESULTADO_IGNORADO
     */
    private function procesarMerchantOrder(string $merchantOrderId, array $contexto = []): string
    {
        $accessToken = $this->resolverAccessToken($contexto, collect());

        if (empty($accessToken)) {
            Log::error('MercadoPago webhook sin token resoluble para merchant order', [
                'event' => 'mp_webhook_token_resolution_error_merchant_order',
                'merchant_order_id' => $merchantOrderId,
                'contexto' => $contexto,
            ]);
            $this->logConfiguracionEntorno('token_resolution_error_merchant_order', ['merchant_order_id' => $merchantOrderId]);

            return self::RESULTADO_RETRY;
        }

        $orden = null;
        $statusCode = null;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get(self::BASE_URL_MP . '/merchant_orders/' . $merchantOrderId);

            $statusCode = $response->status();

            if ($response->successful()) {
                $orden = json_decode($response->body());
            } else {
                Log::warning('No se pudo obtener merchant order via webhook (API no disponible o sin acceso)', [
                    'merchant_order_id' => $merchantOrderId,
                    'status_code' => $statusCode,
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Excepción obteniendo merchant order via webhook', [
                'merchant_order_id' => $merchantOrderId,
                'error' => $e->getMessage(),
            ]);
        }

        // 403 (PolicyAgent) o 404 (orden ajena): el webhook de tipo "payment" cubre
        // este caso, no tiene sentido reintentar esta orden.
        if (!$orden && in_array($statusCode, [403, 404], true)) {
            return self::RESULTADO_IGNORADO;
        }

        if (!$orden) {
            return self::RESULTADO_RETRY;
        }

        $pagosAprobados = $this->extraerPagosAprobados($orden->payments ?? []);

        Log::info('Merchant order obtenida via webhook', [
            'merchant_order_id' => $merchantOrderId,
            'status' => $orden->status ?? null,
            'payments_count' => count($orden->payments ?? []),
            'approved_count' => count($pagosAprobados)
        ]);

        $resultadoGlobal = self::RESULTADO_PROCESADO;

        foreach ($pagosAprobados as $paymentId) {
            $resultado = $this->procesarPagoPorId($paymentId, $contexto + [
                'source' => 'merchant_order',
            ]);

            if ($resultado === self::RESULTADO_RETRY) {
                $resultadoGlobal = self::RESULTADO_RETRY;
            }
        }

        return $resultadoGlobal;
    }

    private function resolverTokenEmpresaPorUserId($userId): ?string
    {
        $userId = trim((string) $userId);
        if ($userId === '') {
            return null;
        }

        $empresa = Empresa::query()
            ->where('MP_USER_ID', $userId)
            ->first();

        if (!$empresa || empty($empresa->MP_ACCESS_TOKEN)) {
            return null;
        }

        return (string) $empresa->MP_ACCESS_TOKEN;
    }

    private function resolverTokenDesdeServicios($serviciosPagar): ?string
    {
        if ($serviciosPagar->isEmpty()) {
            return null;
        }

        $empresa = $serviciosPagar->first()->servicio->empresa ?? null;

        if (!$empresa || empty($empresa->MP_ACCESS_TOKEN)) {
            return null;
        }

        return (string) $empresa->MP_ACCESS_TOKEN;
    }

    private function logConfiguracionEntorno(string $contexto, array $extra = []): void
    {
        Log::info('MercadoPago entorno configuración', $extra + [
            'contexto' => $contexto,
            'mercadopago_test' => env('MERCADOPAGO_TEST', true),
            'app_url' => config('app.url'),
            'base_url_efectiva' => MercadoPagoApiService::getBaseUrl(),
            'config_cache' => app()->configurationIsCached(),
        ]);
    }

    /**
     * Extraer los IDs de los pagos aprobados de una merchant order.
     * Acepta tanto objetos del SDK (MercadoPago\Resources\MerchantOrder\Payment)
     * como arrays planos.
     *
     * @return string[]
     */
    private function extraerPagosAprobados(array $payments): array
    {
        $ids = [];

        foreach ($payments as $payment) {
            $id = is_object($payment) ? ($payment->id ?? null) : ($payment['id'] ?? null);
            $status = is_object($payment) ? ($payment->status ?? null) : ($payment['status'] ?? null);

            if ($id && $status === 'approved') {
                $ids[] = (string) $id;
            }
        }

        return $ids;
    }

    /**
     * Resolver los servicios a pagar asociados a una referencia externa.
     *
     * Formatos soportados:
     *  - "servicio_pagar_123"        → un servicio individual
     *  - "cliente_impagos_3_2"       → todos los servicios impagos del cliente 3 de la empresa 2
     *
     * @return \Illuminate\Support\Collection<int, ServicioPagar>
     */
    private function resolverServiciosPorReferencia(string $externalReference)
    {
        if (preg_match('/^servicio_pagar_(\d+)$/', $externalReference, $matches)) {
            $servicioPagar = ServicioPagar::with('servicio.empresa')->find($matches[1]);

            return $servicioPagar ? collect([$servicioPagar]) : collect();
        }

        if (preg_match('/^cliente_impagos_(\d+)_(\d+)$/', $externalReference, $matches)) {
            $clienteId = (int) $matches[1];
            $empresaId = (int) $matches[2];

            return ServicioPagar::with('servicio.empresa')
                ->where('cliente_id', $clienteId)
                ->where('estado', 'impago')
                ->whereHas('servicio', function ($query) use ($empresaId) {
                    $query->where('empresa_id', $empresaId);
                })
                ->get();
        }

        return collect();
    }

    /**
     * Distribuir el monto neto de un pago agrupado entre los servicios que lo componen,
     * de forma proporcional a su subtotal. El redondeo sobrante se asigna al último
     * servicio para que la suma de importes coincida con el neto.
     *
     * @return array<int, float>
     */
    private function distribuirImportes($serviciosPagar, float $montoNeto): array
    {
        $importes = [];
        $totalServicios = $serviciosPagar->count();
        $sumaSubtotales = $serviciosPagar->sum(fn ($sp) => (float) $sp->precio * (int) $sp->cantidad);

        if ($sumaSubtotales <= 0) {
            $importeBase = round($montoNeto / max(1, $totalServicios), 2);
            foreach ($serviciosPagar as $i => $sp) {
                $importes[$sp->id] = $i === $totalServicios - 1
                    ? round($montoNeto - $importeBase * max(0, $totalServicios - 1), 2)
                    : $importeBase;
            }

            return $importes;
        }

        $acumulado = 0.0;
        foreach ($serviciosPagar as $i => $sp) {
            $subtotal = (float) $sp->precio * (int) $sp->cantidad;
            if ($i === $totalServicios - 1) {
                $importes[$sp->id] = round($montoNeto - $acumulado, 2);
            } else {
                $importe = round($montoNeto * ($subtotal / $sumaSubtotales), 2);
                $importes[$sp->id] = $importe;
                $acumulado += $importe;
            }
        }

        return $importes;
    }

    /**
     * Procesar notificación de pago
     */
    private function processPaymentNotification(ServicioPagar $servicioPagar, $payment, array $contexto = [])
    {
        $paymentStatus = $payment->status;
        $paymentId = $payment->id;

        Log::info('Procesando notificación de pago', [
            'servicio_pagar_id' => $servicioPagar->id,
            'payment_id' => $paymentId,
            'status' => $paymentStatus,
            'current_estado' => $servicioPagar->estado
        ]);

        switch ($paymentStatus) {
            case 'approved':
                if ($servicioPagar->estado !== 'pago') {
                    $updated = ServicioPagar::query()
                        ->where('id', $servicioPagar->id)
                        ->where('estado', '!=', 'pago')
                        ->update([
                        'estado' => 'pago',
                        'mp_payment_id' => $paymentId
                    ]);

                    if ($updated === 0) {
                        Log::info('Pago aprobado omitido por actualización concurrente', [
                            'servicio_pagar_id' => $servicioPagar->id,
                            'payment_id' => $paymentId,
                        ]);
                        break;
                    }

                    $servicioPagar->estado = 'pago';
                    $servicioPagar->mp_payment_id = (string) $paymentId;

                    // Buscar el id de la forma de pago MercadoPago
                    $formaPago = \App\Models\FormaPago::where('nombre', 'MercadoPago')->first();
                    $formaPagoId = $formaPago ? $formaPago->id : 1; // fallback a 1 si no existe

                    $montoBruto = (float) ($payment->transaction_amount ?? 0);
                    $montoNeto = (float) ($payment->transaction_details->net_received_amount ?? $montoBruto);
                    $comision = $montoBruto - $montoNeto;
                    $comentario = sprintf(
                        'Webhook MP ID:%s Bruto:%.2f Neto:%.2f Comision:%.2f',
                        $paymentId,
                        $montoBruto,
                        $montoNeto,
                        $comision
                    );

                    // Crear registro en la tabla pagos si no existe, usando firstOrCreate para evitar duplicados
                    $pago = Pagos::firstOrCreate(
                        [
                            'id_servicio_pagar' => $servicioPagar->id,
                            'forma_pago' => $formaPagoId
                        ],
                        [
                            'id_usuario' => $this->resolverUsuarioSistemaId(),
                            'importe' => $contexto['importe'] ?? $montoNeto,
                            'comentario' => $comentario
                        ]
                    );

                    if ($pago->wasRecentlyCreated) {
                        Log::info('Pago registrado exitosamente via webhook', [
                            'servicio_pagar_id' => $servicioPagar->id,
                            'payment_id' => $paymentId
                        ]);
                    }

                    // Notificar al cliente que su pago fue recibido (correo + WhatsApp con comprobante)
                    $this->notificarPagoRealizado($servicioPagar, $paymentId, $montoBruto);
                }
                break;

            case 'rejected':
            case 'cancelled':
                $servicioPagar->update([
                    'estado' => 'impago',
                    'mp_payment_id' => null
                ]);
                Log::info('Pago rechazado/cancelado', [
                    'servicio_pagar_id' => $servicioPagar->id,
                    'payment_id' => $paymentId
                ]);
                break;

            case 'pending':
            case 'in_process':
                $servicioPagar->update([
                    'mp_payment_id' => $paymentId
                ]);
                Log::info('Pago pendiente', [
                    'servicio_pagar_id' => $servicioPagar->id,
                    'payment_id' => $paymentId
                ]);
                break;

            default:
                Log::info('Estado de pago no manejado', [
                    'status' => $paymentStatus,
                    'payment_id' => $paymentId
                ]);
        }
    }

    /**
     * Notificar al cliente (correo y WhatsApp con comprobante PDF) cuando un pago
     * de MercadoPago es aprobado. Replica el flujo manual de ConfirmarPago.
     */
    private function notificarPagoRealizado(ServicioPagar $servicioPagar, string $paymentId, float $montoBruto): void
    {
        try {
            $notificationKey = 'mp_payment_notification_' . $servicioPagar->id . '_' . $paymentId;
            if (!Cache::add($notificationKey, 1, 60 * 60 * 24 * 7)) {
                Log::info('Notificación de pago omitida por idempotencia', [
                    'servicio_pagar_id' => $servicioPagar->id,
                    'payment_id' => $paymentId,
                    'cache_key' => $notificationKey,
                ]);

                return;
            }

            $cliente = $servicioPagar->cliente;
            $servicio = $servicioPagar->servicio;

            if (!$cliente || !$servicio) {
                return;
            }

            $empresa = $servicio->empresa ?? null;

            $mensaje = "Hola {$cliente->nombre},\n\n";
            $mensaje .= "Le informamos desde {$empresa->nombre} que hemos recibido su pago.\n";
            $mensaje .= "Detalles del pago:\n";
            $mensaje .= "• Servicio: {$servicio->nombre}\n";
            $mensaje .= "• Forma de pago: MercadoPago - \${$montoBruto}\n";
            $mensaje .= "• ID de pago MP: {$paymentId}\n";
            $mensaje .= "• Fecha: " . now()->format('d/m/Y H:i') . "\n\n";
            $mensaje .= "¡Gracias por su preferencia!";

            $datosPDF = [
                'nombreCliente' => $cliente->nombre,
                'dniCliente' => $cliente->dni,
                'nombreServicio' => $servicio->nombre,
                'nombreEmpresa' => $empresa->nombre ?? '',
                'cantidad' => $servicioPagar->cantidad,
                'precioUnitario' => $servicioPagar->precio,
                'forma_pago' => 'MercadoPago',
                'importe' => $montoBruto,
                'forma_pago2' => null,
                'importe2' => 0,
                'comentario' => 'Pago webhook MercadoPago ID: ' . $paymentId,
                'fechaPago' => now()->format('d/m/Y H:i'),
                'logoEmpresa' => $empresa->logo ?? null,
            ];

            $datosCorreo = [
                'total' => $montoBruto,
                'forma_pago' => 'MercadoPago',
                'importe' => $montoBruto,
                'forma_pago2' => null,
                'importe2' => 0,
                'logoEmpresa' => $empresa->logo ?? null,
            ];

            ProcesarPagoJob::dispatch(
                $cliente->telefono,
                $mensaje,
                $empresa->instanciaWS ?? null,
                $empresa->tokenWS ?? null,
                $datosPDF,
                $datosCorreo,
                $servicioPagar->id
            );

            Log::info('Notificación de pago realizado despachada via webhook', [
                'servicio_pagar_id' => $servicioPagar->id,
                'payment_id' => $paymentId,
                'importe' => $montoBruto,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al despachar notificación de pago realizado via webhook', [
                'servicio_pagar_id' => $servicioPagar->id,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
