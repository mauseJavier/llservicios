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
use App\Helpers\DniHelper;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

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
    private const USUARIO_PAGO_ONLINE_EMAIL = 'pago.online@example.com';

    private function resolverUsuarioSistemaId(): int
    {
        $idUsuarioPago = \App\Models\User::where('email', self::USUARIO_PAGO_ONLINE_EMAIL)->value('id');
        return $idUsuarioPago ? (int) $idUsuarioPago : 0;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Obtener filtros de fecha, si no se proporcionan, usar la fecha actual
        // $fechaInicio = $request->get('fecha_inicio', date('Y-m-d'));
        // $fechaFin = $request->get('fecha_fin', date('Y-m-d'));

        $fechaInicio = $request->get('fecha_inicio');
        $fechaFin = $request->get('fecha_fin');

        $buscar = $request->get('buscar');
        $usuarioId = $request->get('usuario_id');
        
        // Construir condiciones de fecha
        $condicionFecha = '';
        $parametros = [];
        
        // Añadir filtro por empresa del usuario autenticado
        $empresaId = auth()->user()->empresa_id;
        $parametros[] = $empresaId; // este parametro es g.empresa_id en la consulta empresas
        $parametros[] = $empresaId;// este parametro es c.empresa_id en la consulta usuarios
        
        if ($fechaInicio) {
            $condicionFecha .= ' AND DATE(a.created_at) >= ?';
            $parametros[] = $fechaInicio;
        }
        
        if ($fechaFin) {
            $condicionFecha .= ' AND DATE(a.created_at) <= ?';
            $parametros[] = $fechaFin;
        }

        // Añadir condición de búsqueda por cliente si existe
        $condicionBusqueda = '';
        if ($buscar) {
            $condicionBusqueda = ' AND (e.nombre LIKE ? OR e.correo LIKE ? OR e.dni LIKE ?)';
            $parametros[] = '%' . $buscar . '%';
            $parametros[] = '%' . $buscar . '%';
            $parametros[] = '%' . $buscar . '%';
        }

        // Añadir filtro por usuario si existe
        $condicionUsuario = '';
        if ($usuarioId) {
            $condicionUsuario = ' AND a.id_usuario = ?';
            $parametros[] = $usuarioId;
        }

        // Consulta con Eloquent usando relaciones
        $query = Pagos::query()
            ->with([
                'servicioPagar.cliente.empresas',
                'servicioPagar.servicio',
                'usuario',
                'formaPago',
                'formaPago2'
            ])
            // FILTRO 1: Filtrar por empresa del servicio (CRÍTICO para multi-tenant)
            ->whereHas('servicioPagar.servicio', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            // FILTRO 2: Filtrar por empresa del cliente
            ->whereHas('servicioPagar.cliente.empresas', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        // Aplicar filtro de fecha inicio
        if ($fechaInicio) {
            $query->whereDate('created_at', '>=', $fechaInicio);
        }

        // Aplicar filtro de fecha fin
        if ($fechaFin) {
            $query->whereDate('created_at', '<=', $fechaFin);
        }

        // Aplicar búsqueda por cliente
        if ($buscar) {
            $query->whereHas('servicioPagar.cliente', function($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('correo', 'LIKE', "%{$buscar}%")
                  ->orWhere('dni', 'LIKE', "%{$buscar}%");
            });
        }

        // Aplicar filtro por usuario
        if ($usuarioId) {
            $query->where('id_usuario', $usuarioId);
        }

        $tiposFacturaAfip = [1, 6, 11];
        $tiposNotaCreditoAfip = [3, 8, 13];

        // Obtener resultados ordenados por fecha de pago descendente y mapear campos calculados
        $datos = $query->orderByDesc('created_at')->get()->map(function($pago) use ($tiposFacturaAfip, $tiposNotaCreditoAfip) {
            $tipoComprobante = (int) ($pago->afip_tipo_comprobante ?? 0);

            $pago->idServicioPagar = $pago->servicioPagar->id ?? null;
            $pago->nombreUsuario = $pago->usuario->name ?? ((int) $pago->id_usuario === 0 ? 'Pago Online' : 'Desconocido');
            $pago->Servicio = $pago->servicioPagar->servicio->nombre ?? null;
            $pago->Cliente = $pago->servicioPagar->cliente->nombre ?? null;
            $pago->idCliente = $pago->servicioPagar->cliente->id ?? null;
            $pago->formaPago = $pago->formaPago->nombre ?? null;
            $pago->formaPago2 = $pago->formaPago2->nombre ?? null;
            $pago->tipoComprobanteResumen = 'Sin AFIP';
            $pago->tipoComprobanteColor = '#757575';

            if (!empty($pago->afip_cae)) {
                if (in_array($tipoComprobante, $tiposNotaCreditoAfip, true)) {
                    $pago->tipoComprobanteResumen = 'Nota de Crédito';
                    $pago->tipoComprobanteColor = '#d32f2f';
                } elseif (in_array($tipoComprobante, $tiposFacturaAfip, true)) {
                    $pago->tipoComprobanteResumen = 'Factura';
                    $pago->tipoComprobanteColor = '#2e7d32';
                } else {
                    $pago->tipoComprobanteResumen = $pago->tipo_comprobante_nombre;
                    $pago->tipoComprobanteColor = '#1565c0';
                }
            }

            return $pago;
        });

        // Obtener resumen de pagos por forma de pago usando Eloquent
        $queryResumen = Pagos::query()
            ->with(['formaPago', 'servicioPagar.servicio', 'servicioPagar.cliente.empresas', 'usuario'])
            // FILTRO 1: Filtrar por empresa del servicio
            ->whereHas('servicioPagar.servicio', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            // FILTRO 2: Filtrar por empresa del cliente
            ->whereHas('servicioPagar.cliente.empresas', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        // Aplicar filtros de fecha
        if ($fechaInicio) {
            $queryResumen->whereDate('created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $queryResumen->whereDate('created_at', '<=', $fechaFin);
        }

        // Aplicar búsqueda por cliente
        if ($buscar) {
            $queryResumen->whereHas('servicioPagar.cliente', function($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('correo', 'LIKE', "%{$buscar}%")
                  ->orWhere('dni', 'LIKE', "%{$buscar}%");
            });
        }

        // Aplicar filtro por usuario
        if ($usuarioId) {
            $queryResumen->where('id_usuario', $usuarioId);
        }

        // Obtener pagos y agrupar por forma de pago (incluyendo forma_pago y forma_pago2)
        $pagosParaResumen = $queryResumen->get();
        
        $resumenAgrupado = [];
        
        foreach ($pagosParaResumen as $pago) {
            // Sumar importe de forma_pago principal
            if ($pago->formaPago) {
                $nombreFormaPago = $pago->formaPago->nombre;
                if (!isset($resumenAgrupado[$nombreFormaPago])) {
                    $resumenAgrupado[$nombreFormaPago] = (object)[
                        'formaPago' => $nombreFormaPago,
                        'cantidadPagos' => 0,
                        'totalImporte' => 0
                    ];
                }
                $resumenAgrupado[$nombreFormaPago]->cantidadPagos++;
                $resumenAgrupado[$nombreFormaPago]->totalImporte += $pago->importe;
            }
            
            // Sumar importe de forma_pago2 si existe
            if ($pago->formaPago2 && $pago->importe2) {
                $nombreFormaPago2 = $pago->formaPago2->nombre;
                if (!isset($resumenAgrupado[$nombreFormaPago2])) {
                    $resumenAgrupado[$nombreFormaPago2] = (object)[
                        'formaPago' => $nombreFormaPago2,
                        'cantidadPagos' => 0,
                        'totalImporte' => 0
                    ];
                }
                $resumenAgrupado[$nombreFormaPago2]->cantidadPagos++;
                $resumenAgrupado[$nombreFormaPago2]->totalImporte += $pago->importe2;
            }
        }

        // Convertir a array y ordenar por totalImporte descendente
        $resumenPagos = array_values($resumenAgrupado);
        usort($resumenPagos, function($a, $b) {
            return $b->totalImporte <=> $a->totalImporte;
        });

        // Obtener resumen por usuario que realizó el cobro usando Eloquent
        $queryResumenUsuario = Pagos::query()
            ->with(['usuario', 'servicioPagar.cliente.empresas', 'servicioPagar.servicio'])
            ->select(
                'id_usuario',
                DB::raw('COUNT(DISTINCT id) as cantidadPagos'),
                DB::raw('SUM(importe) as totalImporte1'),
                DB::raw('SUM(COALESCE(importe2, 0)) as totalImporte2'),
                DB::raw('SUM(importe + COALESCE(importe2, 0)) as totalImporte')
            )
            // FILTRO 1: Filtrar por empresa del servicio (CRÍTICO para multi-tenant)
            ->whereHas('servicioPagar.servicio', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            // FILTRO 2: Filtrar por empresa del cliente
            ->whereHas('servicioPagar.cliente.empresas', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        // Aplicar filtro de fecha inicio
        if ($fechaInicio) {
            $queryResumenUsuario->whereDate('created_at', '>=', $fechaInicio);
        }

        // Aplicar filtro de fecha fin
        if ($fechaFin) {
            $queryResumenUsuario->whereDate('created_at', '<=', $fechaFin);
        }

        // Aplicar búsqueda por cliente
        if ($buscar) {
            $queryResumenUsuario->whereHas('servicioPagar.cliente', function($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('correo', 'LIKE', "%{$buscar}%")
                  ->orWhere('dni', 'LIKE', "%{$buscar}%");
            });
        }

        // Aplicar filtro por usuario
        if ($usuarioId) {
            $queryResumenUsuario->where('id_usuario', $usuarioId);
        }

        $resumenPorUsuario = $queryResumenUsuario
            ->groupBy('id_usuario')
            ->orderByDesc(DB::raw('SUM(importe + COALESCE(importe2, 0))'))
            ->get()
            ->map(function($resumen) {
                return (object)[
                    'usuarioId' => $resumen->id_usuario,
                    'nombreUsuario' => $resumen->usuario->name ?? ((int) $resumen->id_usuario === 0 ? 'Pago Online' : 'Desconocido'),
                    'cantidadPagos' => $resumen->cantidadPagos,
                    'totalImporte1' => $resumen->totalImporte1,
                    'totalImporte2' => $resumen->totalImporte2,
                    'totalImporte' => $resumen->totalImporte
                ];
            });

        // Obtener resumen de facturación AFIP (facturados vs no facturados)
        $queryResumenFacturacion = Pagos::query()
            ->with(['servicioPagar.servicio', 'servicioPagar.cliente.empresas', 'usuario'])
            // FILTRO 1: Filtrar por empresa del servicio
            ->whereHas('servicioPagar.servicio', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            // FILTRO 2: Filtrar por empresa del cliente
            ->whereHas('servicioPagar.cliente.empresas', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        // Aplicar filtros de fecha
        if ($fechaInicio) {
            $queryResumenFacturacion->whereDate('created_at', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $queryResumenFacturacion->whereDate('created_at', '<=', $fechaFin);
        }

        // Aplicar búsqueda por cliente
        if ($buscar) {
            $queryResumenFacturacion->whereHas('servicioPagar.cliente', function($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('correo', 'LIKE', "%{$buscar}%")
                  ->orWhere('dni', 'LIKE', "%{$buscar}%");
            });
        }

        // Aplicar filtro por usuario
        if ($usuarioId) {
            $queryResumenFacturacion->where('id_usuario', $usuarioId);
        }

        // Calcular totales de facturas, notas de credito y no facturados
        $pagosParaFacturacion = $queryResumenFacturacion->get();

        $sumarTotales = function ($coleccion) {
            return $coleccion->sum(function($pago) {
                return $pago->importe + ($pago->importe2 ?? 0);
            });
        };

        $facturas = $pagosParaFacturacion->filter(function($pago) use ($tiposFacturaAfip) {
            return !empty($pago->afip_cae) && in_array((int) ($pago->afip_tipo_comprobante ?? 0), $tiposFacturaAfip, true);
        });

        $notasCredito = $pagosParaFacturacion->filter(function($pago) use ($tiposNotaCreditoAfip) {
            return !empty($pago->afip_cae) && in_array((int) ($pago->afip_tipo_comprobante ?? 0), $tiposNotaCreditoAfip, true);
        });

        $noFacturados = $pagosParaFacturacion->filter(function($pago) {
            return empty($pago->afip_cae);
        });

        $totalFacturas = $sumarTotales($facturas);
        $totalNotasCredito = $sumarTotales($notasCredito);
        $totalNoFacturados = $sumarTotales($noFacturados);
        $totalNeto = $sumarTotales($pagosParaFacturacion);
        $basePorcentajeFacturacion = $totalFacturas + abs($totalNotasCredito) + $totalNoFacturados;

        $resumenFacturacion = [
            'facturas' => (object)[
                'cantidad' => $facturas->count(),
                'total' => $totalFacturas,
                'promedio' => $facturas->count() > 0
                    ? $totalFacturas / $facturas->count()
                    : 0
            ],
            'notasCredito' => (object)[
                'cantidad' => $notasCredito->count(),
                'total' => $totalNotasCredito,
                'promedio' => $notasCredito->count() > 0
                    ? $totalNotasCredito / $notasCredito->count()
                    : 0
            ],
            'noFacturados' => (object)[
                'cantidad' => $noFacturados->count(),
                'total' => $totalNoFacturados,
                'promedio' => $noFacturados->count() > 0
                    ? $totalNoFacturados / $noFacturados->count()
                    : 0
            ],
            'neto' => (object)[
                'cantidad' => $pagosParaFacturacion->count(),
                'total' => $totalNeto,
                'promedio' => $pagosParaFacturacion->count() > 0
                    ? $totalNeto / $pagosParaFacturacion->count()
                    : 0
            ],
            'total' => (object)[
                'cantidad' => $pagosParaFacturacion->count(),
                'total' => $totalNeto,
                'basePorcentaje' => $basePorcentajeFacturacion
            ]
        ];

        // return $datos;


                // Número de elementos por página
                $perPage = 100;

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

                // Obtener usuarios de la empresa para el filtro
                $usuarios = \App\Models\User::where('empresa_id', $empresaId)
                    ->orderBy('name', 'asc')
                    ->get();

            

                //agregar a los usuarios el usuario email like %pago% que es para todas las empresass
                $usuarioPagoOnline = \App\Models\User::where('email', self::USUARIO_PAGO_ONLINE_EMAIL)->first();
                if ($usuarioPagoOnline && !$usuarios->contains('id', $usuarioPagoOnline->id)) {
                    $usuarios->push($usuarioPagoOnline);
                }

                return view('pagos.pagos', compact('pagos', 'resumenPagos', 'resumenPorUsuario', 'resumenFacturacion', 'fechaInicio', 'fechaFin', 'buscar', 'usuarios', 'usuarioId'))->render();
    }

    public function PagosVer ($idServicioPagar){

        $empresaId = auth()->user()->empresa_id;

        // Consulta con Eloquent
        $pago = Pagos::query()
            ->with([
                'servicioPagar.cliente.empresas',
                'servicioPagar.servicio',
                'usuario',
                'formaPago',
                'formaPago2'
            ])
            ->whereHas('servicioPagar', function($q) use ($idServicioPagar) {
                $q->where('id', $idServicioPagar);
            })
            ->whereNull('afip_nc_de_pago_id')
            // FILTRO 1: Filtrar por empresa del servicio
            ->whereHas('servicioPagar.servicio', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            // FILTRO 2: Filtrar por empresa del cliente
            ->whereHas('servicioPagar.cliente.empresas', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->first();

        if (!$pago) {
            abort(404, 'Pago no encontrado');
        }

        // Agregar campos calculados
        $datos = $pago;
        $datos->idServicioPagar = $pago->servicioPagar->id ?? null;
        $datos->nombreUsuario = $pago->usuario->name ?? ((int) $pago->id_usuario === 0 ? 'Pago Online' : 'Desconocido');
        $datos->Servicio = $pago->servicioPagar->servicio->nombre ?? null;
        $datos->Cliente = $pago->servicioPagar->cliente->nombre ?? null;
        $datos->idCliente = $pago->servicioPagar->cliente->id ?? null;
        $datos->formaPago = $pago->formaPago->nombre ?? null;
        $datos->formaPago2 = $pago->formaPago2->nombre ?? null;

        return view('pagos.pagosVer',['datos'=>$datos])->render();
    }

    public function pagoPDF($idServicioPagar,Request $request){
        $empresaId = auth()->user()->empresa_id;

        // Consulta con Eloquent
        $pago = Pagos::query()
            ->with([
                'servicioPagar.cliente.empresas',
                'servicioPagar.servicio',
                'usuario',
                'formaPago',
                'formaPago2'
            ])
            ->whereHas('servicioPagar', function($q) use ($idServicioPagar) {
                $q->where('id', $idServicioPagar);
            })
            ->whereNull('afip_nc_de_pago_id')
            // FILTRO 1: Filtrar por empresa del servicio
            ->whereHas('servicioPagar.servicio', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            // FILTRO 2: Filtrar por empresa del cliente
            ->whereHas('servicioPagar.cliente.empresas', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->first();

        if (!$pago) {
            abort(404, 'Pago no encontrado');
        }

        // Agregar campos calculados
        $datos = $pago;
        $datos->idServicioPagar = $pago->servicioPagar->id ?? null;
        $datos->nombreUsuario = $pago->usuario->name ?? ((int) $pago->id_usuario === 0 ? 'Pago Online' : 'Desconocido');
        $datos->Servicio = $pago->servicioPagar->servicio->nombre ?? null;
        $datos->Cliente = $pago->servicioPagar->cliente->nombre ?? null;
        $datos->idCliente = $pago->servicioPagar->cliente->id ?? null;
        $datos->formaPago = $pago->formaPago->nombre ?? null;
        $datos->formaPago2 = $pago->formaPago2->nombre ?? null;

        $pdf = Pdf::loadView('pdf.pagoPDF',['datos'=>$datos,'empresa'=>Empresa::find(Auth::user()->empresa_id)]);


        if($request->tamañoPapel == '80MM'){
            //tamaño tiket 
            //tamaño A4 en vertical
            // $pdf->setPaper('A7', 'portrait');
            $pdf->set_paper(array(0, 0, 226.772, 800), 'portrait');
        }


        $nombreArchivo= $datos->Cliente.' '.$datos->Servicio.'.pdf';
        return $pdf->stream($nombreArchivo, [ "Attachment" => true]);
        // return $pdf->download($nombreArchivo, [ "Attachment" => true]);

    }

    /**
     * Genera PDF de factura AFIP
     */
    public function facturaAfipPDF($pagoId, Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        // Buscar el pago con sus relaciones
        $pago = Pagos::with(['servicioPagar.cliente', 'servicioPagar.servicio'])
            ->find($pagoId);

        if (!$pago) {
            abort(404, 'Pago no encontrado');
        }

        // Verificar que el pago tenga factura AFIP
        if (!$pago->tieneFacturaAfip()) {
            abort(400, 'Este pago no tiene factura AFIP generada');
        }

        // Recargar el pago con relaciones completas para el PDF
        $pago = Pagos::query()
            ->with([
                'servicioPagar.cliente.empresas',
                'servicioPagar.servicio',
                'usuario',
                'formaPago',
                'formaPago2'
            ])
            ->where('id', $pagoId)
            // FILTRO 1: Filtrar por empresa del servicio
            ->whereHas('servicioPagar.servicio', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            // FILTRO 2: Filtrar por empresa del cliente
            ->whereHas('servicioPagar.cliente.empresas', function($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->first();

        if (!$pago) {
            abort(404, 'Datos del pago no encontrados');
        }

        // Agregar campos calculados
        $datos = $pago;
        $datos->idServicioPagar = $pago->servicioPagar->id ?? null;
        $datos->nombreUsuario = $pago->usuario->name ?? ((int) $pago->id_usuario === 0 ? 'Pago Online' : 'Desconocido');
        $datos->Servicio = $pago->servicioPagar->servicio->nombre ?? null;
        $datos->Cliente = $pago->servicioPagar->cliente->nombre ?? null;
        $datos->idCliente = $pago->servicioPagar->cliente->id ?? null;
        $datos->formaPago = $pago->formaPago->nombre ?? null;
        $datos->formaPago2 = $pago->formaPago2->nombre ?? null;

        $empresa = Empresa::find(Auth::user()->empresa_id);
        $cliente = $pago->servicioPagar->cliente ?? null;

        $qrBase64 = $this->generarQrAfipBase64ParaPdf($pago, $empresa, $cliente, $datos);

        
        // Configurar tamaño de papel
        if ($request->tamañoPapel == '80MM') {
            // Generar PDF
            $pdf = Pdf::loadView('pdf.facturaAfip80', [
                'datos' => $datos,
                'empresa' => $empresa,
                'pago' => $pago,
                'cliente' => $cliente,
                'qrBase64' => $qrBase64
            ]);
            $pdf->set_paper(array(0, 0, 226.772, 800), 'portrait');
        }else {
            // Generar PDF con tamaño A4
            $pdf = Pdf::loadView('pdf.facturaAfip', [
                'datos' => $datos,
                'empresa' => $empresa,
                'pago' => $pago,
                'cliente' => $cliente,
                'qrBase64' => $qrBase64
            ]);
            $pdf->setPaper('A4', 'portrait');
        }

        $nombreArchivo = 'Factura_AFIP_' . $pago->afip_cae . '.pdf';
        return $pdf->stream($nombreArchivo, ["Attachment" => false]);
    }

    /**
     * Genera el QR AFIP en Base64 para el PDF de factura
     */
    private function generarQrAfipBase64ParaPdf($pago, $empresa, $cliente, $datos)
    {
        try {
            $fechaEmision = \Carbon\Carbon::parse($pago->created_at)->format('Y-m-d');
            $importeTotal = (float) (($datos->importe ?? 0) + ($datos->importe2 ?? 0));
            $dniNormalizado = $cliente?->dni ? ($cliente->dni) : null;
            $tipoDocRec = $dniNormalizado ? (strlen($dniNormalizado) === 11 ? 80 : 96) : null;

            $qrPayload = [
                'ver' => 1,
                'fecha' => $fechaEmision,
                'cuit' => (int) ($empresa->cuit ?? 0),
                'ptoVta' => (int) ($pago->afip_punto_venta ?? 0),
                'tipoCmp' => (int) ($pago->afip_tipo_comprobante ?? 0),
                'nroCmp' => (int) ($pago->afip_numero_comprobante ?? 0),
                'importe' => round($importeTotal, 2),
                'moneda' => 'PES',
                'ctz' => 1,
                'tipoCodAut' => 'E',
                'codAut' => (int) $pago->afip_cae,
            ];

            if(env('APP_ENV') === 'local') {

                \Log::info('Generando QR AFIP', [
                    'pago_id' => $pago->id,
                    'qrPayload' => $qrPayload
                ]);
            }

            if ($tipoDocRec && $dniNormalizado) {
                $qrPayload['tipoDocRec'] = (int) $tipoDocRec;
                $qrPayload['nroDocRec'] = (int) $dniNormalizado;
            }

            $qrJson = json_encode($qrPayload, JSON_UNESCAPED_SLASHES);
            $qrBase64Data = base64_encode($qrJson);
            $qrUrl = 'https://www.arca.gob.ar/fe/qr/?p=' . $qrBase64Data;

            $writer = new PngWriter();
            $qrCode = new QrCode($qrUrl);
            $result = $writer->write($qrCode);

            return $result->getDataUri();
        } catch (\Exception $e) {
            \Log::warning('No se pudo generar QR AFIP', [
                'pago_id' => $pago->id ?? null,
                'error' => $e->getMessage()
            ]);
            return null;
        }
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
    public function destroy(Pagos $pago)
    {
        try {
            $usuario = Auth::user();

            // Solo Admin (2) o Super (3) pueden eliminar pagos
            if (!in_array($usuario->role_id, [2, 3])) {
                return redirect()->back()
                    ->withErrors(['No tienes permisos para eliminar pagos. Solo usuarios Admin o Super pueden hacerlo.']);
            }

            $pago->load(['servicioPagar.servicio']);
            $servicioPagar = $pago->servicioPagar;

            if (!$servicioPagar) {
                return redirect()->back()
                    ->withErrors(['El pago no tiene un servicio relacionado o ya no existe.']);
            }

            if (!$servicioPagar->servicio) {
                return redirect()->back()
                    ->withErrors(['No se pudo determinar la empresa del servicio asociado al pago.']);
            }

            // Multi-tenant: validar que el pago pertenezca a la empresa del usuario
            if ((int) $servicioPagar->servicio->empresa_id !== (int) $usuario->empresa_id) {
                return redirect()->back()
                    ->withErrors(['No puedes eliminar pagos de otra empresa.']);
            }

            // Regla AFIP: si hay CAE, no permitir eliminar
            if ($pago->tieneFacturaAfip()) {
                return redirect()->back()
                    ->withErrors(['No se puede eliminar este pago porque tiene factura AFIP (CAE: ' . $pago->afip_cae . ').']);
            }

            DB::transaction(function () use ($pago, $servicioPagar, $usuario) {
                $servicioPagar->update([
                    'estado' => 'impago',
                ]);

                $pagoId = $pago->id;
                $servicioPagarId = $servicioPagar->id;

                $pago->delete();

                \Log::info('Pago eliminado y servicio revertido a impago', [
                    'usuario_id' => $usuario->id,
                    'usuario_nombre' => $usuario->name,
                    'role_id' => $usuario->role_id,
                    'empresa_id' => $usuario->empresa_id,
                    'pago_id' => $pagoId,
                    'servicio_pagar_id' => $servicioPagarId,
                    'fecha_eliminacion' => now(),
                ]);
            });

            return redirect()->route('Pagos')
                ->with('status', 'Pago eliminado correctamente. El servicio fue revertido a IMPAGO.');
        } catch (\Exception $e) {
            \Log::error('Error al eliminar pago', [
                'usuario_id' => Auth::id(),
                'pago_id' => $pago->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['Error al eliminar el pago: ' . $e->getMessage()]);
        }
    }

    /**
     * Generar preferencia de pago para un servicio específico usando el SDK oficial de MercadoPago
     */
    public function generarPago(ServicioPagar $servicioPagar)
    {
        try {
            $usuario = Auth::user();
            // Comparar DNI normalizando CUIT/DNI
            if (!DniHelper::compararDni($servicioPagar->cliente->dni, $usuario->dni)) {
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
                $idUsuarioPago = $this->resolverUsuarioSistemaId();
                $montoBruto = (float) ($payment->transaction_amount ?? $servicioPagar->total);
                $montoNeto = (float) ($payment->transaction_details->net_received_amount ?? $montoBruto);
                $comision = $montoBruto - $montoNeto;
                $comentario = sprintf(
                    'Callback MP ID:%s Bruto:%.2f Neto:%.2f Comision:%.2f',
                    $payment_id,
                    $montoBruto,
                    $montoNeto,
                    $comision
                );
                Pagos::create([
                    'id_servicio_pagar' => $servicioPagar->id,
                    'id_usuario' => $idUsuarioPago,
                    'forma_pago' => $formaPagoId,
                    'importe' => $montoNeto,
                    'comentario' => $comentario
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
