<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


use App\Mail\NotificacionCuotaMail;
use Illuminate\Support\Facades\Mail;

class EnviarCorreoController extends Controller
{

    public function NotificacionNuevoServicio($idServicioPagar){

        $datos = DB::select('SELECT
                                b.nombre AS nombreCliente,
                                c.nombre AS nombreServicio,
                                a.cantidad AS cantidadServicio,
                                a.precio AS precioServicio,
                                a.created_at AS fechaServicio,
                                b.correo as correoCliente
                            FROM
                                servicio_pagar a,
                                clientes b,
                                servicios c
                            WHERE
                                a.cliente_id = b.id AND a.servicio_id = c.id AND a.id = ?', [$idServicioPagar]);

              
        $datos[0]->fechaServicio =  Carbon::parse($datos[0]->fechaServicio)->format('d-m-Y');

        // return $datos;
        
 
        try {

            // return (new NotificacionCuotaMail($datos))->render();


            $correo = Mail::to($datos[0]->correoCliente)->send(new NotificacionCuotaMail($datos));

            return redirect()->route('ServiciosImpagos')
            ->with('status','Mensaje Correcto');
        } catch (Exception $e) {
            return back()->withErrors(['Dirección de correo inválida']);
        }



    }
}
