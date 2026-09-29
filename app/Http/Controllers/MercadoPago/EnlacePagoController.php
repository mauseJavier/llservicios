<?php

namespace App\Http\Controllers\MercadoPago;

use App\Http\Controllers\Controller;
use App\Models\ServicioPagar;
use App\Services\MercadoPago\MercadoPagoLinkService;

class EnlacePagoController extends Controller
{
    public function __construct(private MercadoPagoLinkService $linkService)
    {
    }

    /**
     * Link firmado de pago individual.
     * Si el servicio ya está pago no se llega a MercadoPago (evita dobles cobros).
     */
    public function individual(ServicioPagar $servicioPagar)
    {
        if ($servicioPagar->estado !== 'impago') {
            return view('mercadopago.pagado', [
                'titulo' => 'Su pago ya fue registrado',
                'mensaje' => 'Este servicio no tiene pagos pendientes. Si cree que es un error, contacte a su proveedor de servicios.',
            ]);
        }

        $checkoutUrl = $this->linkService->linkIndividual($servicioPagar);

        if (empty($checkoutUrl)) {
            return view('mercadopago.pagado', [
                'titulo' => 'No se pudo generar el pago',
                'mensaje' => 'Intente nuevamente más tarde o contacte a su proveedor de servicios.',
            ]);
        }

        return redirect($checkoutUrl);
    }

    /**
     * Link firmado de pago agrupado (todos los servicios impagos del cliente en la empresa).
     * Se resuelven los impagos en el momento del clic, por lo que siempre refleja el estado actual.
     */
    public function cliente(int $clienteId, int $empresaId)
    {
        $serviciosImpagos = ServicioPagar::with('servicio.empresa')
            ->where('cliente_id', $clienteId)
            ->where('estado', 'impago')
            ->whereHas('servicio', function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId);
            })
            ->get();

        if ($serviciosImpagos->isEmpty()) {
            return view('mercadopago.pagado', [
                'titulo' => 'No tiene pagos pendientes',
                'mensaje' => 'Todos sus servicios están al día. Si cree que es un error, contacte a su proveedor de servicios.',
            ]);
        }

        $checkoutUrl = $this->linkService->linkClienteImpagos($clienteId, $empresaId, $serviciosImpagos);

        if (empty($checkoutUrl)) {
            return view('mercadopago.pagado', [
                'titulo' => 'No se pudo generar el pago',
                'mensaje' => 'Intente nuevamente más tarde o contacte a su proveedor de servicios.',
            ]);
        }

        return redirect($checkoutUrl);
    }
}