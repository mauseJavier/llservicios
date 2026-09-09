<?php

namespace App\Services\MercadoPago;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\ServicioPagar;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class MercadoPagoLinkService
{
    public function __construct(private MercadoPagoApiService $apiService)
    {
    }

    /**
     * Generar el link firmado de pago individual de un servicio.
     * Al hacer clic, la app re-valida el estado y crea la preferencia en ese momento.
     */
    public static function urlEnlaceIndividual(int $servicioPagarId): string
    {
        return self::signedEnlace('pago.enlace.individual', ['servicioPagar' => $servicioPagarId]);
    }

    /**
     * Generar el link firmado de pago agrupado (servicios impagos de un cliente en una empresa).
     * Al hacer clic, la app resuelve los servicios impagos actuales y crea la preferencia.
     */
    public static function urlEnlaceCliente(int $clienteId, int $empresaId): string
    {
        return self::signedEnlace('pago.enlace.cliente', ['clienteId' => $clienteId, 'empresaId' => $empresaId]);
    }

    /**
     * Generar una URL firmada usando la URL pública (ngrok en local, dominio en prod).
     * La firma se calcula sobre el host + path + query, por lo que se fuerza el root y el
     * scheme correctos antes de firmar para que la validación coincida al ser alcanzada.
     */
    private static function signedEnlace(string $routeName, array $parameters): string
    {
        $baseUrl = MercadoPagoApiService::getBaseUrl();
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

        URL::forceRootUrl($baseUrl);
        URL::forceScheme($scheme);

        try {
            return URL::signedRoute($routeName, $parameters);
        } finally {
            URL::forceRootUrl(null);
            URL::forceScheme(null);
        }
    }

    /**
     * Generar el link de pago individual de un servicio a pagar.
     *
     * @return string|null URL de checkout o null si no es posible generarla.
     */
    public function linkIndividual(ServicioPagar $servicioPagar): ?string
    {
        $servicioPagar->loadMissing(['servicio.empresa', 'cliente']);
        $empresa = $servicioPagar->servicio->empresa ?? null;

        if (!$empresa || empty($empresa->MP_ACCESS_TOKEN)) {
            Log::info('MercadoPagoLink - Empresa sin token, no se genera link individual', [
                'empresa_id' => $empresa->id ?? null,
                'servicio_pagar_id' => $servicioPagar->id,
            ]);
            return null;
        }

        $items = MercadoPagoApiService::itemsDesdeServiciosPagar([$servicioPagar]);

        $result = $this->apiService->crearPreferenciaCheckout(
            $items,
            'servicio_pagar_' . $servicioPagar->id,
            $servicioPagar->cliente->correo ?? null,
            $empresa->MP_ACCESS_TOKEN,
            null,
            null,
            (int) $empresa->id
        );

        if (empty($result['success'])) {
            Log::warning('MercadoPagoLink - Error generando link individual', [
                'servicio_pagar_id' => $servicioPagar->id,
                'error' => $result['error'] ?? 'desconocido',
            ]);
            return null;
        }

        return $result['checkout_url'];
    }

    /**
     * Generar el link de pago agrupado con los servicios impagos de un cliente
     * dentro de una empresa (una sola preferencia con todos los items).
     *
     * @param int $clienteId
     * @param int $empresaId
     * @param iterable $serviciosPagar Modelos ServicioPagar o filas stdClass de DB::select
     * @return string|null URL de checkout o null si no es posible generarla.
     */
    public function linkClienteImpagos(int $clienteId, int $empresaId, iterable $serviciosPagar): ?string
    {
        $empresa = Empresa::find($empresaId);

        if (!$empresa || empty($empresa->MP_ACCESS_TOKEN)) {
            Log::info('MercadoPagoLink - Empresa sin token, no se genera link agrupado', [
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId,
            ]);
            return null;
        }

        $items = MercadoPagoApiService::itemsDesdeServiciosPagar($serviciosPagar);

        if (empty($items)) {
            return null;
        }

        $cliente = Cliente::find($clienteId);

        $result = $this->apiService->crearPreferenciaCheckout(
            $items,
            'cliente_impagos_' . $clienteId . '_' . $empresaId,
            $cliente->correo ?? null,
            $empresa->MP_ACCESS_TOKEN,
            null,
            null,
            (int) $empresa->id
        );

        if (empty($result['success'])) {
            Log::warning('MercadoPagoLink - Error generando link agrupado', [
                'cliente_id' => $clienteId,
                'empresa_id' => $empresaId,
                'error' => $result['error'] ?? 'desconocido',
            ]);
            return null;
        }

        return $result['checkout_url'];
    }
}