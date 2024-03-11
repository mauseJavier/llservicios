<?php

namespace App\Http\Controllers;


use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

use Illuminate\Http\Request;

use App\Http\Requests\StorePagosRequest;
use App\Http\Requests\UpdatePagosRequest;
use App\Models\Pagos;

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
                                    forma_pagos f
                                WHERE
                                    a.id_servicio_pagar = b.id AND a.id_usuario = c.id AND b.servicio_id = d.id AND b.cliente_id = e.id AND a.forma_pago = f.id');

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


                return view('pagos.pagos', compact('pagos'))->render();
    }

    public function PagosVer ($idServicioPagar){

        // return $idServicioPagar;

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
                            forma_pagos f
                        WHERE
                            a.id_servicio_pagar = b.id AND a.id_usuario = c.id AND b.servicio_id = d.id AND b.cliente_id = e.id AND a.forma_pago = f.id and b.id = ?',[$idServicioPagar] );
        
        // return $datos;

        return view('pagos.pagosVer',['datos'=>$datos[0]])->render();
    }

    public function pagoPDF($idServicioPagar,Request $request){
        // return view('pdf.ejemploPDF',)->render();

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
                    forma_pagos f
                WHERE
                    a.id_servicio_pagar = b.id AND a.id_usuario = c.id AND b.servicio_id = d.id AND b.cliente_id = e.id AND a.forma_pago = f.id and b.id = ?',[$idServicioPagar] );

            // return $datos;
            // return $request;

            // return view('pagos.pagosVer',['datos'=>$datos[0]])->render();

        $pdf = Pdf::loadView('pdf.pagoPDF',['datos'=>$datos[0]]);


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
}
