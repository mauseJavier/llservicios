<?php

namespace App\Listeners;

use App\Events\NuevoServicioPagarEvent;
use App\Jobs\EnviarEmailNuvoServicioJob;
use App\Jobs\EnviarWhatsAppNuevoServicioJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NuevoServicioPagarListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            // Obtener datos del servicio
            $datos = DB::select('SELECT
                                    b.id AS clienteId,
                                    b.nombre AS nombreCliente,
                                    b.telefono AS telefonoCliente,
                                    b.correo as correoCliente,
                                    c.nombre AS nombreServicio,
                                    a.cantidad AS cantidadServicio,
                                    a.precio AS precioServicio,
                                    a.created_at AS fechaServicio,
                                    d.nombre AS nombreEmpresa
                                FROM
                                    servicio_pagar a,
                                    clientes b,
                                    servicios c,
                                    empresas d
                                WHERE
                                    a.cliente_id = b.id 
                                    AND a.servicio_id = c.id 
                                    AND c.empresa_id = d.id
                                    AND a.id = ?', [$event->idServicioPagar]);

            // Validar que se encontraron datos
            if (empty($datos)) {
                \Log::warning('NuevoServicioPagarListener - No se encontraron datos', [
                    'idServicioPagar' => $event->idServicioPagar
                ]);
                return;
            }

            // Validar correo válido
            if (empty($datos[0]->correoCliente) || $datos[0]->correoCliente == 'correo@correo.com') {
                \Log::info('NuevoServicioPagarListener - Cliente sin correo válido', [
                    'idServicioPagar' => $event->idServicioPagar
                ]);
                return;
            }

            // Despachar Job de Email con cola
            EnviarEmailNuvoServicioJob::dispatch($event->idServicioPagar);
            \Log::info('Email encolado', [
                'idServicioPagar' => $event->idServicioPagar,
                'correoCliente' => $datos[0]->correoCliente
            ]);

            // Despachar Job de WhatsApp si el cliente tiene teléfono
            if (!empty($datos[0]->telefonoCliente)) {
                // Obtener el servicio_id del servicio_pagar (2 queries simples para evitar error de MariaDB con LIMIT en IN subquery)
                $servicioPagar = DB::table('servicio_pagar')
                    ->where('id', $event->idServicioPagar)
                    ->first(['servicio_id']);

                if ($servicioPagar) {
                    // Obtener la empresa usando el servicio_id
                    $empresaId = DB::table('servicios')
                        ->where('id', $servicioPagar->servicio_id)
                        ->value('empresa_id');

                    if ($empresaId) {
                        $empresa = \App\Models\Empresa::find($empresaId);

                        if ($empresa && $empresa->instanciaWS && $empresa->tokenWS) {
                            EnviarWhatsAppNuevoServicioJob::dispatch(
                                $event->idServicioPagar,
                                $empresa->instanciaWS,
                                $empresa->tokenWS
                            );
                            \Log::info('WhatsApp encolado', [
                                'idServicioPagar' => $event->idServicioPagar,
                                'telefonoCliente' => $datos[0]->telefonoCliente
                            ]);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('Error en NuevoServicioPagarListener: ' . $e->getMessage(), [
                'idServicioPagar' => $event->idServicioPagar ?? 'unknown',
                'exception' => $e
            ]);
        }
    }
}
