<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ServicioPagarController extends Controller
{
    
    public function ServiciosImpagos(Request $request){

        $usuario = Auth::user();

        $datos = DB::select('SELECT
                                a.id AS idServicioPagar,
                                e.nombre AS nombreCliente,
                                e.dni as dniCliente,
                                b.nombre AS nombreServicio,
                                c.nombre AS nombreEmpresa,
                                a.precio,
                                a.estado,
                                a.created_at AS fechaCreacion
                            FROM
                                servicio_pagar a,
                                servicios b,
                                empresas c,
                                clientes e
                            WHERE
                                a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = e.id AND a.estado = ? AND c.id = ?
                            ORDER BY a.id DESC', ['impago',$usuario->empresa_id,]);


        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($datos);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $datosPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

                //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        // $clientesPaginados->appends(['Buscar' => $datoBuscado]);
    
        // return $clientes;
        
     return view('servicios.ServiciosImpagos',['servicios'=>$datosPaginados,
               ]
            )->render();
    }

    public function ServicioPagarBuscarCliente(Request $request,$estado){

        // return [$estado,$request];

        $datoBuscado= $request->buscar;

        $fechaDesde = (isset($request->fechaDesde)) ? $request->fechaDesde : '2000-01-01';
        $fechaHasta  = (isset($request->fechaHasta)) ? $request->fechaHasta : '3000-01-01';

        $usuario = Auth::user();

        $datos = DB::select('SELECT
                                a.id AS idServicioPagar,
                                e.nombre AS nombreCliente,
                                e.dni as dniCliente,
                                b.nombre AS nombreServicio,
                                c.nombre AS nombreEmpresa,
                                a.precio,
                                a.estado,
                                a.created_at AS fechaCreacion
                            FROM
                                servicio_pagar a,
                                servicios b,
                                empresas c,
                                clientes e
                            WHERE
                                a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = e.id AND a.created_at >= ? AND a.created_at <= ? AND  a.estado = ? AND c.id = ? AND
                                ( e.nombre LIKE ? or
                                e.correo LIKE ? or
                                e.dni LIKE ? or
                                b.nombre LIKE ?)
                            ORDER BY a.id DESC', [ $fechaDesde,
                                                    $fechaHasta,
                                                    $estado,
                                                    $usuario->empresa_id,
                                                    '%'.$datoBuscado. '%',
                                                    '%'.$datoBuscado. '%',
                                                    '%'.$datoBuscado. '%',
                                                    '%'.$datoBuscado. '%']);


        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($datos);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $datosPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

                //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        $datosPaginados->appends(['buscar' => $datoBuscado]);
    
        // return $datosPaginados;
        
        if ($estado == 'pago'){
            
            return view('servicios.ServiciosPagos',['servicios'=>$datosPaginados, 'buscar'=>$datoBuscado,'fechaDesde'=>$fechaDesde,'fechaHasta'=>$fechaHasta]
            )->render();

        }else{
            return view('servicios.ServiciosImpagos',['servicios'=>$datosPaginados, 'buscar'=>$datoBuscado,'fechaDesde'=>$fechaDesde,'fechaHasta'=>$fechaHasta]
            )->render();
        }


    }

    public function ServiciosPagos(Request $request){


        $usuario = Auth::user();

        $datos = DB::select('SELECT
                                a.id AS idServicioPagar,
                                e.nombre AS nombreCliente,
                                e.dni as dniCliente,
                                b.nombre AS nombreServicio,
                                c.nombre AS nombreEmpresa,
                                a.precio,
                                a.estado,
                                a.created_at AS fechaCreacion
                            FROM
                                servicio_pagar a,
                                servicios b,
                                empresas c,
                                clientes e
                            WHERE
                                a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = e.id AND a.estado = ? AND c.id = ?
                            ORDER BY a.id DESC', ['pago',$usuario->empresa_id,]);


        // Número de elementos por página
        $perPage = 15;

        // Página actual obtenida de la consulta de la URL (puedes usar Request::input('page') en un controlador real)
        $paginaActual = (isset($request->page)) ? $request->page : 1;

        // Crear una colección para usar el método slice
        $colección = new Collection($datos);

        // Obtener los elementos para la página actual
        $items = $colección->slice(($paginaActual - 1) * $perPage, $perPage)->all();

        // Crear una instancia de LengthAwarePaginator
        $datosPaginados = new LengthAwarePaginator($items, count($colección), $perPage, $paginaActual, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]);

                //ESTO ES PARA EL PAGINADOR
        // $usuarios->withPath('/admin/users');
        // $clientesPaginados->appends(['Buscar' => $datoBuscado]);
    
        // return $clientes;
        
     return view('servicios.ServiciosPagos',['servicios'=>$datosPaginados, 'fechaDesde'=>date('Y-m-d', strtotime('first day of this month'))
                                                                            ,'fechaHasta'=>date('Y-m-d', strtotime('last day of this month')),]
            )->render();
    }
}
