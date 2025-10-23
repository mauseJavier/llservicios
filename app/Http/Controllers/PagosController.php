<?php

namespace App\Http\Controllers;


use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

use Illuminate\Http\Request;

use App\Http\Requests\StorePagosRequest;
use App\Http\Requests\UpdatePagosRequest;
use App\Models\Pagos;
use App\Models\Empresa;
use App\Models\ServicioPagar;
use App\Services\MercadoPagoService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


use Barryvdh\DomPDF\Facade\Pdf;

class PagosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Obtener filtros de fecha
        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');
        
        // Construir condiciones de fecha solo si se proporcionan
        $condicionFecha = '';
        $parametros = [];
        
        if ($fechaInicio) {
            $condicionFecha .= ' AND DATE(a.created_at) >= ?';
            $parametros[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $condicionFecha .= ' AND DATE(a.created_at) <= ?';
            $parametros[] = $fechaFin;
        }

        // Añadir filtro por empresa del usuario autenticado
        $empresaId = auth()->user()->empresa_id;
        $condicionFecha .= ' AND g.empresa_id = ?';
        $parametros[] = $empresaId;

        $datos = DB::select('SELECT
                                    a.*,
                                    b.id as idServicioPagar,
                                    c.name AS nombreUsuario,
                                    d.nombre AS Servicio,
                                    e.nombre AS Cliente,
                                    e.id as idCliente,
                                    f.nombre AS formaPago
                                FROM
                                    pagos a,
                                    servicio_pagar b,
                                    users c,
                                    servicios d,
                                    clientes e,
                                    forma_pagos f,
                                    cliente_empresa g
                                WHERE
                                    a.id_servicio_pagar = b.id 
                                    AND a.id_usuario = c.id 
                                    AND b.servicio_id = d.id 
                                    AND b.cliente_id = e.id 
                                    AND a.forma_pago = f.id
                                    AND e.id = g.cliente_id' . $condicionFecha, $parametros);

        // Obtener resumen de pagos por forma de pago con filtros de fecha y empresa
        // Necesitamos filtros separados para el resumen ya que la estructura de la consulta es diferente
        $condicionFechaResumen = '';
        $parametrosResumen = [];
        
        if ($fechaInicio) {
            $condicionFechaResumen .= ' AND DATE(a.created_at) >= ?';
            $parametrosResumen[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $condicionFechaResumen .= ' AND DATE(a.created_at) <= ?';
            $parametrosResumen[] = $fechaFin;
        }
        
        $condicionFechaResumen .= ' AND g.empresa_id = ?';
        $parametrosResumen[] = $empresaId;

        $resumenPagos = DB::select('SELECT
                                        f.nombre AS formaPago,
                                        COUNT(a.id) AS cantidadPagos,
                                        SUM(a.importe) AS totalImporte
                                    FROM
                                        pagos a
                                        INNER JOIN forma_pagos f ON a.forma_pago = f.id
                                        INNER JOIN servicio_pagar b ON a.id_servicio_pagar = b.id
                                        INNER JOIN clientes e ON b.cliente_id = e.id
                                        INNER JOIN cliente_empresa g ON e.id = g.cliente_id
                                    WHERE 1=1' . $condicionFechaResumen . '
                                    GROUP BY
                                        f.id, f.nombre
                                    ORDER BY
                                        totalImporte DESC', $parametrosResumen);

        // return $datos;


                // Número de elementos por página
                $perPage = 15;

                // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
                $paginaActual = (isset($request->page)) ? $request->page : 1;
        
                // Crear una colección para usar el método slice
                $colección = new Collection($datos);
        
                // Obtener los elementos para la página actual
                $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();
        
                // Crear una instancia de LengthAwarePaginator
                $pagos = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                ]);
        
                        //ESTO ES PARA EL PAGINADOR
                // $usuarios->withPath('/admin/users');
                // $clientesPaginados->appends(['Buscar' => $datoBuscado]);
            
                // return $pagos;


                return view('pagos.pagos', compact('pagos', 'resumenPagos'))->render();
    }

    public function PagosVer ($idServicioPagar){

        // return $idServicioPagar;

        $empresaId = auth()->user()->empresa_id;

        $datos = DB::select('SELECT
                            a.*,
                            b.id as idServicioPagar,
                            c.name AS nombreUsuario,
                            d.nombre AS Servicio,
                            e.nombre AS Cliente,
                            e.id as idCliente,
                            f.nombre AS formaPago
                        FROM
                            pagos a,
                            servicio_pagar b,
                            users c,
                            servicios d,
                            clientes e,
                            forma_pagos f,
                            cliente_empresa g
                        WHERE
                            a.id_servicio_pagar = b.id 
                            AND a.id_usuario = c.id 
                            AND b.servicio_id = d.id 
                            AND b.cliente_id = e.id 
                            AND a.forma_pago = f.id 
                            AND e.id = g.cliente_id 
                            AND g.empresa_id = ? 
                            AND b.id = ?',[$empresaId, $idServicioPagar] );
        
        // return $datos;

        return view('pagos.pagosVer',['datos'=>$datos[0]])->render();
    }

    public function pagoPDF($idServicioPagar,Request $request){
        // return view('pdf.ejemploPDF',)->render();

        $empresaId = auth()->user()->empresa_id;

        $datos = DB::select('SELECT
                    a.*,
                    b.id as idServicioPagar,
                    c.name AS nombreUsuario,
                    d.nombre AS Servicio,
                    e.nombre AS Cliente,
                    e.id as idCliente,
                    f.nombre AS formaPago
                FROM
                    pagos a,
                    servicio_pagar b,
                    users c,
                    servicios d,
                    clientes e,
                    forma_pagos f,
                    cliente_empresa g
                WHERE
                    a.id_servicio_pagar = b.id 
                    AND a.id_usuario = c.id 
                    AND b.servicio_id = d.id 
                    AND b.cliente_id = e.id 
                    AND a.forma_pago = f.id 
                    AND e.id = g.cliente_id 
                    AND g.empresa_id = ? 
                    AND b.id = ?',[$empresaId, $idServicioPagar] );

            // return $datos;
            // return $request;

            // return array('datos'=>$datos,'usuario'=>Empresa::find(Auth::user()->empresa_id) );

            // return view('pagos.pagosVer',['datos'=>$datos[0]])->render();

        $pdf = Pdf::loadView('pdf.pagoPDF',['datos'=>$datos[0],'empresa'=>Empresa::find(Auth::user()->empresa_id)]);


        if($request->tamañoPapel == '80MM'){
            //tamaño tiket 
            //tamaño A4 en vertical
            // $pdf->setPaper('A7', 'portrait');
            $pdf->set_paper(array(0, 0, 226.772, 500), 'portrait');
        }


        $nombreArchivo= $datos[0]->Cliente.' '.$datos[0]->Servicio.'.pdf';
        return $pdf->stream($nombreArchivo, [ "Attachment" => true]);
        // return $pdf->download($nombreArchivo, [ "Attachment" => true]);

    }

    public function ConfirmarPago (Request $request){

        return $request;

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePagosRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Pagos $pagos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pagos $pagos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePagosRequest $request, Pagos $pagos)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pagos $pagos)
    {
        //
    }

    /**
     * Generar preferencia de pago para un servicio específico
     */
    public function generarPago(ServicioPagar $servicioPagar)
    {
        try {
            // Verificar que el servicio pertenece al usuario autenticado
            $usuario = Auth::user();
            if ($servicioPagar->cliente->dni !== $usuario->dni) {
                return redirect()->back()->with('error', 'No tienes permiso para pagar este servicio.');
            }

            // Verificar que el servicio está impago
            if ($servicioPagar->estado !== 'impago') {
                return redirect()->back()->with('error', 'Este servicio ya ha sido pagado.');
            }

            // Cargar las relaciones necesarias
            $servicioPagar->load(['servicio.empresa', 'cliente']);

            // Obtener credenciales de MercadoPago de la empresa
            $empresa = $servicioPagar->servicio->empresa;
            
            // Verificar que la empresa tiene credenciales de MercadoPago configuradas
            if (empty($empresa->MP_ACCESS_TOKEN)) {
                return redirect()->back()->with('error', 'La empresa no tiene configuradas las credenciales de MercadoPago.');
            }

            // Preparar datos para la preferencia de pago
            $items = [[
                'id' => 'servicio_' . $servicioPagar->id,
                'title' => $servicioPagar->servicio->nombre,
                'description' => 'Pago de ' . $servicioPagar->servicio->nombre . ' - ' . $empresa->nombre,
                'picture_url' => $servicioPagar->servicio->imagen,
                'category_id' => 'services',
                'quantity' => (int) $servicioPagar->cantidad,
                'currency_id' => 'ARS',
                'unit_price' => (float) $servicioPagar->precio
            ]];

            $preference_data = [
                'items' => $items,
                'payer' => [
                    'name' => $servicioPagar->cliente->nombre,
                    'email' => $servicioPagar->cliente->correo ?: $usuario->email,
                    'identification' => [
                        'type' => 'DNI',
                        'number' => (string) $servicioPagar->cliente->dni
                    ]
                ],
                'back_urls' => [
                    // 'success' => route('pago.success', $servicioPagar->id),
                    // 'failure' => route('pago.failure', $servicioPagar->id),
                    // 'pending' => route('pago.pending', $servicioPagar->id)

                    'success' => 'https://localhost:1234/pago/success/' . $servicioPagar->id,
                    'failure' => 'https://localhost:1234/pago/failure/' . $servicioPagar->id,
                    'pending' => 'https://localhost:1234/pago/pending/' . $servicioPagar->id
                ],
                'auto_return' => 'approved',
                'external_reference' => 'servicio_pagar_' . $servicioPagar->id,
                'statement_descriptor' => $empresa->nombre,
                'expires' => true,
                'expiration_date_from' => now()->toISOString(),
                'expiration_date_to' => now()->addDays(30)->toISOString()
            ];

            // Crear instancia del servicio con las credenciales de la empresa
            // Temporalmente, configurar las credenciales de la empresa
            config(['services.mercadopago.access_token' => $empresa->MP_ACCESS_TOKEN]);
            
            $mercadoPagoService = new MercadoPagoService();
            
            // Crear la preferencia de pago
            $preference = $mercadoPagoService->createPreference($preference_data);

            if ($preference && isset($preference['init_point'])) {
                // Guardar el payment_id en la base de datos para tracking
                $servicioPagar->update([
                    'mp_preference_id' => $preference['id'] ?? null
                ]);

                // Redirigir al checkout de MercadoPago
                return redirect($preference['init_point']);
            } else {
                return redirect()->back()->with('error', 'Error al crear la preferencia de pago. Intenta nuevamente.');
            }

        } catch (\Exception $e) {
            \Log::error('Error al generar pago MercadoPago: ' . $e->getMessage(), [
                'servicio_pagar_id' => $servicioPagar->id,
                'usuario_id' => Auth::id(),
                'error' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Error interno al procesar el pago. Intenta nuevamente.');
        }
    }

    /**
     * Callback de pago exitoso
     */
    public function pagoSuccess(ServicioPagar $servicioPagar, Request $request)
    {
        try {
            // Obtener información del pago desde MercadoPago
            $payment_id = $request->get('payment_id');
            $status = $request->get('status');
            $external_reference = $request->get('external_reference');

            \Log::info('Callback Success MercadoPago', [
                'servicio_pagar_id' => $servicioPagar->id,
                'payment_id' => $payment_id,
                'status' => $status,
                'external_reference' => $external_reference
            ]);

            // Actualizar estado del servicio a pagado
            if ($status === 'approved') {
                $servicioPagar->update([
                    'estado' => 'pago',
                    'mp_payment_id' => $payment_id
                ]);

                // Crear registro en la tabla pagos
                Pagos::create([
                    'id_servicio_pagar' => $servicioPagar->id,
                    'id_usuario' => Auth::id(),
                    'forma_pago' => 1, // MercadoPago
                    'importe' => $servicioPagar->total,
                    'comentario' => 'Pago procesado por MercadoPago. Payment ID: ' . $payment_id
                ]);

                return redirect()->route('panel')->with('success', 'Pago procesado exitosamente!');
            }

            return redirect()->route('panel')->with('warning', 'El pago está siendo procesado.');

        } catch (\Exception $e) {
            \Log::error('Error en callback success: ' . $e->getMessage());
            return redirect()->route('panel')->with('error', 'Error al procesar el callback del pago.');
        }
    }

    /**
     * Callback de pago fallido
     */
    public function pagoFailure(ServicioPagar $servicioPagar, Request $request)
    {
        \Log::info('Callback Failure MercadoPago', [
            'servicio_pagar_id' => $servicioPagar->id,
            'request_params' => $request->all()
        ]);

        return redirect()->route('panel')->with('error', 'El pago no pudo ser procesado. Intenta nuevamente.');
    }

    /**
     * Callback de pago pendiente
     */
    public function pagoPending(ServicioPagar $servicioPagar, Request $request)
    {
        \Log::info('Callback Pending MercadoPago', [
            'servicio_pagar_id' => $servicioPagar->id,
            'request_params' => $request->all()
        ]);

        return redirect()->route('panel')->with('info', 'Tu pago está siendo procesado. Te notificaremos cuando esté confirmado.');
    }
}
