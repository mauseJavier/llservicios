<?php

namespace App\Services\MercadoPago;

use App\Models\Empresa;
use Illuminate\Http\Request;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
use MercadoPago\Webhook\WebhookSignatureValidator;

/**
 * Valida la autenticidad de las notificaciones de Mercado Pago.
 *
 * Cada empresa opera con su propia cuenta de Mercado Pago, por lo que el
 * secreto de firma se resuelve a partir de la empresa asociada a la
 * notificación (empresa_id en la notification_url o user_id del payload).
 */
class MercadoPagoWebhookSignatureService
{
    public const ESTADO_VALIDA = 'valida';

    public const ESTADO_INVALIDA = 'invalida';

    public const ESTADO_SIN_SECRET = 'sin_secret';

    public function estaHabilitada(): bool
    {
        return (bool) config('services.mercadopago.webhook_signature_enforce', false);
    }

    /**
     * Resolver la empresa dueña de la notificación.
     *
     * Prioridad:
     * 1. empresa_id agregado a la notification_url al crear la preferencia.
     * 2. user_id de la cuenta de Mercado Pago que envía la notificación.
     */
    public function resolverEmpresa(Request $request): ?Empresa
    {
        $empresaId = $request->input('empresa_id');

        if (! empty($empresaId)) {
            $empresa = Empresa::find((int) $empresaId);

            if ($empresa) {
                return $empresa;
            }
        }

        $userId = trim((string) ($request->input('user_id') ?? $request->input('userId') ?? ''));

        if ($userId === '') {
            return null;
        }

        return Empresa::query()
            ->where('MP_USER_ID', $userId)
            ->first();
    }

    /**
     * Identificador del recurso notificado. Mercado Pago lo envía como query
     * param (`data.id`) y la firma se calcula sobre ese valor en minúsculas.
     */
    public function dataId(Request $request): ?string
    {
        // PHP normaliza los puntos de los query params a guiones bajos, por lo
        // que `data.id` llega como `data_id`. Se aceptan ambos formatos.
        $dataId = $request->query('data.id')
            ?? $request->query('data_id')
            ?? $request->input('data.id')
            ?? $request->input('data_id');

        if (! is_scalar($dataId)) {
            return null;
        }

        $dataId = strtolower(trim((string) $dataId));

        return $dataId === '' ? null : $dataId;
    }

    /**
     * @return array{estado: string, empresa: ?Empresa, motivo: ?string}
     */
    public function verificar(Request $request, ?Empresa $empresa = null): array
    {
        $empresa = $empresa ?? $this->resolverEmpresa($request);

        if (! $empresa) {
            return [
                'estado' => self::ESTADO_SIN_SECRET,
                'empresa' => null,
                'motivo' => 'empresa_no_resuelta',
            ];
        }

        $secret = (string) ($empresa->MP_WEBHOOK_SECRET ?? '');

        if ($secret === '') {
            return [
                'estado' => self::ESTADO_SIN_SECRET,
                'empresa' => $empresa,
                'motivo' => 'empresa_sin_secret',
            ];
        }

        try {
            WebhookSignatureValidator::validate(
                $request->header('x-signature'),
                $request->header('x-request-id'),
                $this->dataId($request),
                $secret,
                $this->toleranciaSegundos()
            );

            return [
                'estado' => self::ESTADO_VALIDA,
                'empresa' => $empresa,
                'motivo' => null,
            ];
        } catch (InvalidWebhookSignatureException|\InvalidArgumentException $e) {
            return [
                'estado' => self::ESTADO_INVALIDA,
                'empresa' => $empresa,
                'motivo' => $e->getMessage(),
            ];
        }
    }

    private function toleranciaSegundos(): ?int
    {
        $tolerancia = config('services.mercadopago.webhook_tolerance');

        if ($tolerancia === null || $tolerancia === '') {
            return null;
        }

        return max(0, (int) $tolerancia);
    }
}
