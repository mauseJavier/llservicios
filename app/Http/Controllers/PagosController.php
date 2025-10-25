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


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Importar SDK oficial de MercadoPago
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Exceptions\MPApiException;

// use App\Services\MercadoPagoService;
// use App\Services\MercadoPagoApiService;


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
     * Generar preferencia de pago para un servicio específico usando el SDK oficial de MercadoPago
     */
    public function generarPago(ServicioPagar $servicioPagar)
    {
        try {
            $usuario = Auth::user();
            if ($servicioPagar->cliente->dni !== $usuario->dni) {
                return redirect()->back()->with('error', 'No tienes permiso para pagar este servicio.');
            }

            if ($servicioPagar->estado !== 'impago') {
                return redirect()->back()->with('error', 'Este servicio ya ha sido pagado.');
            }

            $servicioPagar->load(['servicio.empresa', 'cliente']);
            $empresa = $servicioPagar->servicio->empresa;

            if (empty($empresa->MP_ACCESS_TOKEN)) {
                \Log::error('Empresa sin credenciales MercadoPago', [
                    'empresa_id' => $empresa->id,
                    'servicio_pagar_id' => $servicioPagar->id
                ]);
                return redirect()->back()->with('error', 'La empresa no tiene configuradas las credenciales de MercadoPago.');
            }

            // Configurar el SDK oficial
            \MercadoPago\MercadoPagoConfig::setAccessToken($empresa->MP_ACCESS_TOKEN);

            $isSandbox = config('services.mercadopago.sandbox', true);
            $baseUrl = config('app.env') === 'local' ? 'https://prepositionally-vacciniaceous-irving.ngrok-free.dev' : config('app.url');
            $successUrl = $baseUrl . "/pago/success/" . $servicioPagar->id;
            $failureUrl = $baseUrl . "/pago/failure/" . $servicioPagar->id;
            $pendingUrl = $baseUrl . "/pago/pending/" . $servicioPagar->id;
            $webhookUrl = $baseUrl . "/mercadopago/webhook";

            $items = [
                [
                    "title" => $servicioPagar->servicio->nombre,
                    "quantity" => (int) $servicioPagar->cantidad,
                    "unit_price" => (float) $servicioPagar->precio
                ]
            ];

            $preferenceData = [
                "items" => $items,
                "external_reference" => 'servicio_pagar_' . $servicioPagar->id,
                "back_urls" => [
                    "success" => $successUrl,
                    "failure" => $failureUrl,
                    "pending" => $pendingUrl
                ],
                "auto_return" => "approved",
                "notification_url" => $webhookUrl,
            ];

            $client = new \MercadoPago\Client\Preference\PreferenceClient();
            $preference = $client->create($preferenceData);

            if ($preference->id) {
                $servicioPagar->update([
                    'mp_preference_id' => $preference->id
                ]);

                $checkoutUrl = $isSandbox
                    ? ($preference->sandbox_init_point ?? $preference->init_point)
                    : $preference->init_point;

                if (!$checkoutUrl) {
                    \Log::error('No se pudo obtener URL de checkout', [
                        'preference_id' => $preference->id,
                        'init_point' => $preference->init_point ?? null,
                        'sandbox_init_point' => $preference->sandbox_init_point ?? null
                    ]);
                    return redirect()->back()->with('error', 'Error al obtener la URL de pago.');
                }

                return redirect($checkoutUrl);
            } else {
                \Log::error('Error al crear preferencia: Sin ID de preferencia');
                return redirect()->back()->with('error', 'Error al crear la preferencia de pago. Intenta nuevamente.');
            }

        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            \Log::error('Error de API MercadoPago: ' . $e->getMessage(), [
                'servicio_pagar_id' => $servicioPagar->id,
                'status_code' => $e->getApiResponse()->getStatusCode(),
                'api_response' => $e->getApiResponse()->getContent(),
                'error_trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error de la API de MercadoPago: ' . $e->getMessage());
        } catch (\Exception $e) {
            \Log::error('Error al generar pago con SDK MercadoPago: ' . $e->getMessage(), [
                'servicio_pagar_id' => $servicioPagar->id,
                'usuario_id' => Auth::id(),
                'error' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error interno al procesar el pago. Intenta nuevamente.');
        }
    }

    /**
     * Obtener información detallada de un pago usando el SDK oficial de MercadoPago
     */
    public function obtenerInfoPago(string $paymentId)
    {
        try {
            // Configurar el SDK (puedes usar las credenciales por defecto o configurarlas dinámicamente)
            $accessToken = config('services.mercadopago.access_token');
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'error' => 'Credenciales de MercadoPago no configuradas'
                ], 500);
            }

            MercadoPagoConfig::setAccessToken($accessToken);
            MercadoPagoConfig::setRuntimeEnviroment(
                config('services.mercadopago.sandbox', true) 
                    ? MercadoPagoConfig::LOCAL 
                    : MercadoPagoConfig::SERVER
            );

            // Usar el cliente de pagos del SDK moderno
            $paymentClient = new \MercadoPago\Client\Payment\PaymentClient();
            $payment = $paymentClient->get($paymentId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                    'status' => $payment->status,
                    'status_detail' => $payment->status_detail,
                    'transaction_amount' => $payment->transaction_amount,
                    'currency_id' => $payment->currency_id,
                    'external_reference' => $payment->external_reference,
                    'date_created' => $payment->date_created,
                    'date_approved' => $payment->date_approved,
                    'payer' => $payment->payer,
                    'payment_method_id' => $payment->payment_method_id,
                    'payment_type_id' => $payment->payment_type_id
                ]
            ]);

        } catch (MPApiException $e) {
            \Log::error('Error de API MercadoPago obteniendo pago', [
                'payment_id' => $paymentId,
                'status_code' => $e->getApiResponse()->getStatusCode(),
                'api_response' => $e->getApiResponse()->getContent()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error de la API de MercadoPago: ' . $e->getMessage()
            ], $e->getApiResponse()->getStatusCode());

        } catch (\Exception $e) {
            \Log::error('Error obteniendo información de pago', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Callback de pago exitoso
     */
    public function pagoSuccess(ServicioPagar $servicioPagar, Request $request)
    {
        try {
            // Obtener información del pago desde la URL
            $payment_id = $request->get('payment_id');
            $status = $request->get('status');
            $external_reference = $request->get('external_reference');

            \Log::info('Callback Success MercadoPago', [
                'servicio_pagar_id' => $servicioPagar->id,
                'payment_id' => $payment_id,
                'status' => $status,
                'external_reference' => $external_reference
            ]);

            $status_detail = null;

            // Si tenemos payment_id, obtener información detallada usando el SDK moderno
            if ($payment_id) {
                try {
                    // Configurar SDK
                    $empresa = $servicioPagar->servicio->empresa;
                    MercadoPagoConfig::setAccessToken($empresa->MP_ACCESS_TOKEN);
                    MercadoPagoConfig::setRuntimeEnviroment(
                        config('services.mercadopago.sandbox', true) 
                            ? MercadoPagoConfig::LOCAL 
                            : MercadoPagoConfig::SERVER
                    );

                    $paymentClient = new PaymentClient();
                    $payment = $paymentClient->get($payment_id);
                    $status = $payment->status;
                    $status_detail = $payment->status_detail ?? null;

                    \Log::info('Información detallada del pago obtenida', [
                        'payment_id' => $payment_id,
                        'status' => $status,
                        'status_detail' => $status_detail,
                        'transaction_amount' => $payment->transaction_amount ?? null
                    ]);

                } catch (MPApiException $e) {
                    \Log::warning('No se pudo obtener información detallada del pago', [
                        'payment_id' => $payment_id,
                        'error' => $e->getMessage(),
                        'status_code' => $e->getApiResponse()->getStatusCode()
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Error al obtener información del pago', [
                        'payment_id' => $payment_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log automático si el pago fue rechazado
            if ($status === 'rejected') {
                \Log::error('Pago rechazado por MercadoPago', [
                    'servicio_pagar_id' => $servicioPagar->id,
                    'payment_id' => $payment_id,
                    'status_detail' => $status_detail,
                    'external_reference' => $external_reference
                ]);
                return redirect()->route('panel')->with('error', 'El pago fue rechazado. Motivo: ' . ($status_detail ?: 'desconocido'));
            }

            // Actualizar estado del servicio según el status
            if ($status === 'approved') {
                $servicioPagar->update([
                    'estado' => 'pago',
                    'mp_payment_id' => $payment_id
                ]);

                // Crear registro en la tabla pagos
                // Buscar el id de forma de pago correspondiente a MercadoPago
                $formaPagoId = \App\Models\FormaPago::where('nombre', 'like', '%mercadopago%')->value('id') ?? 1;
                Pagos::create([
                    'id_servicio_pagar' => $servicioPagar->id,
                    'id_usuario' => Auth::id(),
                    'forma_pago' => $formaPagoId,
                    'importe' => $servicioPagar->total,
                    'comentario' => 'Pago procesado por MercadoPago. Payment ID: ' . $payment_id
                ]);

                return redirect()->route('panel')->with('success', 'Pago procesado exitosamente!');
            } elseif ($status === 'pending') {
                // Actualizar con estado pendiente
                $servicioPagar->update([
                    'mp_payment_id' => $payment_id
                ]);

                return redirect()->route('panel')->with('info', 'Tu pago está siendo procesado. Te notificaremos cuando esté confirmado.');
            }

            return redirect()->route('panel')->with('warning', 'El pago está siendo procesado.');

        } catch (\Exception $e) {
            \Log::error('Error en callback success: ' . $e->getMessage(), [
                'servicio_pagar_id' => $servicioPagar->id,
                'request_params' => $request->all()
            ]);
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
