<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente;
use App\Models\ServicioPagar;

class PanelController extends Controller
{
    public function index(Request $request){

        $usuario = Auth::user();

        $empresas = null;

        //fecha inicio principio de mes y fecha fin fin de mes
        $conffechaInicio = Carbon::now()->startOfMonth()->toDateString();
        $conffechaFin = Carbon::now()->endOfMonth()->toDateString();
        
        // Obtener parámetros de filtrado
        $fechaDesde = $request->input('fecha_desde', $conffechaInicio);
        $fechaHasta = $request->input('fecha_hasta', $conffechaFin);
        $importeMin = $request->input('importe_min', null);
        $importeMax = $request->input('importe_max', null);
        $nombreServicio = $request->input('nombre_servicio', null);
        $nombreEmpresa = $request->input('nombre_empresa', null);
        
        // Buscar cliente por DNI usando Eloquent
        $cliente = Cliente::where('dni', $usuario->dni)->first();
        
        if ($cliente) {
            // Query base para servicios impagos
            $queryImpagos = $cliente->serviciosPagar()
                ->impagos()
                ->with(['servicio.empresa']);

                //obter los nombres de las empresas relacionadas
                //
            $empresas = $queryImpagos->get()
                ->pluck('servicio.empresa.nombre')
                ->unique()
                ->filter()
                ->values();
            // dd($empresas);

            if($empresas){
                $empresas = null;        
            }

            // Debug: Ver la SQL generada
            // dd($queryImpagos->toSql(), $queryImpagos->getBindings());

            // Query base para servicios pagos
            $queryPagos = $cliente->serviciosPagar()
                ->pagos()
                ->with(['servicio.empresa']);

            // Aplicar filtros de fecha
            if ($fechaDesde) {
                // $queryImpagos->whereDate('servicio_pagar.created_at', '>=', $fechaDesde);
                $queryPagos->whereDate('servicio_pagar.created_at', '>=', $fechaDesde);
            }
            if ($fechaHasta) {
                // $queryImpagos->whereDate('servicio_pagar.created_at', '<=', $fechaHasta);
                $queryPagos->whereDate('servicio_pagar.created_at', '<=', $fechaHasta);
            }

            // Aplicar filtro de nombre de servicio
            if ($nombreServicio) {
                $queryImpagos->whereHas('servicio', function($q) use ($nombreServicio) {
                    $q->where('nombre', 'LIKE', '%' . $nombreServicio . '%');
                });
                $queryPagos->whereHas('servicio', function($q) use ($nombreServicio) {
                    $q->where('nombre', 'LIKE', '%' . $nombreServicio . '%');
                });
            }

            // Aplicar filtro de nombre de empresa
            if ($nombreEmpresa) {
                $queryImpagos->whereHas('servicio.empresa', function($q) use ($nombreEmpresa) {
                    $q->where('nombre', 'LIKE', '%' . $nombreEmpresa . '%');
                });
                $queryPagos->whereHas('servicio.empresa', function($q) use ($nombreEmpresa) {
                    $q->where('nombre', 'LIKE', '%' . $nombreEmpresa . '%');
                });
            }

            // Obtener servicios impagos
            $serviciosImpagos = $queryImpagos->get()
                ->map(function ($servicioPagar) {
                    return (object) [
                        'servicio_id' => $servicioPagar->id,
                        'fechaCobro' => $servicioPagar->created_at,
                        'nombreServicio' => $servicioPagar->servicio->nombre,
                        'linkPago' => $servicioPagar->servicio->linkPago,
                        'imagenServicio' => $servicioPagar->servicio->imagen,
                        'nombreEmpresa' => $servicioPagar->servicio->empresa->nombre,
                        'cantidadServicio' => $servicioPagar->cantidad,
                        'precioServicio' => $servicioPagar->precio,
                        'total' => $servicioPagar->total,
                        'estado' => $servicioPagar->estado,
                        'fecha_vencimiento' => $servicioPagar->fecha_vencimiento,
                        'periodo_servicio' => $servicioPagar->periodo_servicio
                    ];
                });

            // Obtener servicios pagos
            $serviciosPagos = $queryPagos->get()
                ->map(function ($servicioPagar) {
                    return (object) [
                        'servicio_id' => $servicioPagar->id,
                        'fechaCobro' => $servicioPagar->created_at,
                        'nombreServicio' => $servicioPagar->servicio->nombre,
                        'linkPago' => $servicioPagar->servicio->linkPago,
                        'imagenServicio' => $servicioPagar->servicio->imagen,
                        'nombreEmpresa' => $servicioPagar->servicio->empresa->nombre,
                        'cantidadServicio' => $servicioPagar->cantidad,
                        'precioServicio' => $servicioPagar->precio,
                        'total' => $servicioPagar->total,
                        'estado' => $servicioPagar->estado
                    ];
                });

            // Aplicar filtros de importe en la colección (después de mapear)
            if ($importeMin !== null) {
                $serviciosImpagos = $serviciosImpagos->filter(function($s) use ($importeMin) {
                    return $s->total >= $importeMin;
                });
                $serviciosPagos = $serviciosPagos->filter(function($s) use ($importeMin) {
                    return $s->total >= $importeMin;
                });
            }
            if ($importeMax !== null && $importeMax > 0) {
                $serviciosImpagos = $serviciosImpagos->filter(function($s) use ($importeMax) {
                    return $s->total <= $importeMax;
                });
                $serviciosPagos = $serviciosPagos->filter(function($s) use ($importeMax) {
                    return $s->total <= $importeMax;
                });
            }

            // Ordenar por fecha descendente
            $serviciosImpagos = $serviciosImpagos->sortByDesc('fechaCobro')->values();
            $serviciosPagos = $serviciosPagos->sortByDesc('fechaCobro')->values();

        } else {
            $serviciosImpagos = collect();
            $serviciosPagos = collect();
        }

        return view('panel.panel', compact(
            'serviciosImpagos', 
            'serviciosPagos',
            'fechaDesde',
            'fechaHasta',
            'importeMin',
            'importeMax',
            'nombreServicio',
            'nombreEmpresa',
            'empresas'
        ))->render();
    }
}
