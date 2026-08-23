<?php

namespace App\Http\Controllers\MercadoPago;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\ServicioPagar;
use App\Models\Pagos;
use App\Jobs\ProcesarPagoJob;
use App\Services\MercadoPago\MercadoPagoApiService;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\MerchantOrder\MerchantOrderClient;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MercadoPagoWebhookController extends Controller
{
    private const USUARIO_PAGO_ONLINE_EMAIL = 'pago.online@example.com';

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

                $this->procesarPagoPorId($paymentId, [
                    'source' => 'webhook_payment',
                    'user_id' => $userId,
                ]);

                return response()->json(['status' => 'ok'], 200);
            }

            if ($tipo === 'merchant_order') {
                $merchantOrderId = $data['id'] ?? $request->input('id');
                $userId = (string) ($request->input('user_id') ?? $request->input('userId') ?? '');

                if (!$merchantOrderId) {
                    Log::warning('Webhook sin merchant order ID');
                    return response()->json(['error' => 'Merchant order ID missing'], 400);
                }

                $this->procesarMerchantOrder($merchantOrderId, [
                    'source' => 'webhook_merchant_order',
                    'user_id' => $userId,
                ]);

                return response()->json(['status' => 'ok'], 200);
            }

            Log::info('Webhook ignorado - tipo no soportado', ['type' => $tipo]);
            return response()->json(['status' => 'ok'], 200);

        } catch (MPApiException $e) {
            Log::error('Error de API MercadoPago en webhook', [
                'payment_id' => $paymentId ?? null,
                'status_code' => $e->getApiResponse()->getStatusCode(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'API error'], 500);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook MercadoPago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal error'], 500);
        }
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
        ]);
    }

    /**
     * Procesar un pago de MercadoPago a partir de su ID: obtiene el pago,
     * resuelve los servicios asociados, distribuye importes y actualiza/notifica.
     */
    private function procesarPagoPorId(string $paymentId, array $contexto = []): bool
    {
        $lockKey = 'mp_webhook_payment_lock_' . $paymentId;
        $lockTtl = 120;

        if (!Cache::add($lockKey, 1, $lockTtl)) {
            Log::info('Webhook pago omitido por lock activo (posible duplicado concurrente)', [
                'event' => 'mp_webhook_payment_lock_skip',
                'payment_id' => $paymentId,
                'contexto' => $contexto,
            ]);

            return true;
        }

        try {
        $serviciosPagar = collect();
        $tokenReferencia = null;

        if (!empty($contexto['external_reference'])) {
            $serviciosPagar = $this->resolverServiciosPorReferencia((string) $contexto['external_reference']);
            $tokenReferencia = $this->resolverTokenDesdeServicios($serviciosPagar);
        }

        $tokenPorUserId = $this->resolverTokenEmpresaPorUserId($contexto['user_id'] ?? null);
        $tokenGlobal = (string) config('services.mercadopago.access_token');

        $accessToken = $tokenReferencia ?: ($tokenPorUserId ?: $tokenGlobal);

        if (empty($accessToken)) {
            Log::error('MercadoPago webhook sin token resoluble para procesar pago', [
                'event' => 'mp_webhook_token_resolution_error',
                'payment_id' => $paymentId,
                'contexto' => $contexto,
                'token_por_referencia' => !empty($tokenReferencia),
                'token_por_user_id' => !empty($tokenPorUserId),
                'token_global_configurado' => !empty($tokenGlobal),
            ]);
            $this->logConfiguracionEntorno('token_resolution_error_payment', ['payment_id' => $paymentId]);
            return false;
        }

        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(
            config('services.mercadopago.sandbox', true)
                ? MercadoPagoConfig::LOCAL
                : MercadoPagoConfig::SERVER
        );

        // Obtener información del pago
        $paymentClient = new PaymentClient();

        try {
            $payment = $paymentClient->get($paymentId);
        } catch (\Throwable $e) {
            Log::error('Error obteniendo payment en webhook/retorno', [
                'event' => 'mp_webhook_payment_fetch_error',
                'payment_id' => $paymentId,
                'contexto' => $contexto,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        Log::info('Pago obtenido via webhook', [
            'payment_id' => $paymentId,
            'status' => $payment->status,
            'external_reference' => $payment->external_reference
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
            return false;
        }

        // Multi-tenant: usar el token de la empresa que posee el servicio
        $empresa = $serviciosPagar->first()->servicio->empresa ?? null;
        $empresaToken = $empresa->MP_ACCESS_TOKEN ?? null;

        if (empty($empresaToken)) {
            Log::error('Empresa sin MP_ACCESS_TOKEN al procesar pago de webhook', [
                'event' => 'mp_webhook_company_token_missing',
                'payment_id' => $paymentId,
                'empresa_id' => $empresa->id ?? null,
                'external_reference' => $payment->external_reference ?? null,
            ]);
            return false;
        }

        if ($empresaToken !== $accessToken) {
            MercadoPagoConfig::setAccessToken($empresaToken);

            try {
                $payment = $paymentClient->get($paymentId);
            } catch (\Throwable $e) {
                Log::warning('No se pudo reconsultar payment con token de empresa', [
                    'payment_id' => $paymentId,
                    'empresa_id' => $empresa->id ?? null,
                    'error' => $e->getMessage(),
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

        return true;
        } finally {
            Cache::forget($lockKey);
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
     */
    private function procesarMerchantOrder(string $merchantOrderId, array $contexto = []): bool
    {
        $tokenPorUserId = $this->resolverTokenEmpresaPorUserId($contexto['user_id'] ?? null);
        $tokenGlobal = (string) config('services.mercadopago.access_token');
        $accessToken = $tokenPorUserId ?: $tokenGlobal;

        if (empty($accessToken)) {
            Log::error('MercadoPago webhook sin token resoluble para merchant order', [
                'event' => 'mp_webhook_token_resolution_error_merchant_order',
                'merchant_order_id' => $merchantOrderId,
                'contexto' => $contexto,
                'token_por_user_id' => !empty($tokenPorUserId),
                'token_global_configurado' => !empty($tokenGlobal),
            ]);
            $this->logConfiguracionEntorno('token_resolution_error_merchant_order', ['merchant_order_id' => $merchantOrderId]);
            return false;
        }

        MercadoPagoConfig::setAccessToken($accessToken);
        MercadoPagoConfig::setRuntimeEnviroment(
            config('services.mercadopago.sandbox', true)
                ? MercadoPagoConfig::LOCAL
                : MercadoPagoConfig::SERVER
        );

        try {
            $merchantOrderClient = new MerchantOrderClient();
            $order = $merchantOrderClient->get($merchantOrderId);
        } catch (MPApiException $e) {
            Log::warning('No se pudo obtener merchant order via webhook (API no disponible o sin acceso)', [
                'merchant_order_id' => $merchantOrderId,
                'status_code' => $e->getApiResponse()->getStatusCode(),
                'error' => $e->getMessage()
            ]);
            return false;
        }

        $pagosAprobados = $this->extraerPagosAprobados($order->payments ?? []);

        Log::info('Merchant order obtenida via webhook', [
            'merchant_order_id' => $merchantOrderId,
            'status' => $order->status ?? null,
            'payments_count' => count($order->payments ?? []),
            'approved_count' => count($pagosAprobados)
        ]);

        foreach ($pagosAprobados as $paymentId) {
            $this->procesarPagoPorId($paymentId, $contexto + [
                'source' => 'merchant_order',
            ]);
        }

        return true;
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
