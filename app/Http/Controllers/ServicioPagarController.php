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
    
    public function index(Request $request){

        $usuario = Auth::user();

        $datos = DB::select('SELECT
                                a.id AS idServicioPagar,
                                e.nombre AS nombreCliente,
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
                                a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = e.id AND c.id = ?', [$usuario->empresa_id,]);


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
        
     return view('servicios.ServicioPagar',['servicios'=>$datosPaginados,
               ]
            )->render();
    }
}
