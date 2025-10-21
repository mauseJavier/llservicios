<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


use App\Mail\NotificacionCuotaMail;
use App\Mail\NotificacionTodosServiciosMail;
use Illuminate\Support\Facades\Mail;

//IMPORTACION DE JOBSS
use App\Jobs\EnviarEmailNuvoServicioJob;
use App\Jobs\EnviarEmailTodosServiciosImpagosJob;


class EnviarCorreoController extends Controller
{

    public function NotificacionNuevoServicio($idServicioPagar){


        EnviarEmailNuvoServicioJob::dispatch($idServicioPagar);

        return redirect()->route('ServiciosImpagos')
        ->with('status','Mensaje Correcto');



    }

    public function NotificacionTodosServiciosImpagos(){

        // $clientes = DB::select('SELECT
        //                             COUNT(*) AS cantidad,
        //                             a.cliente_id AS cliente_id,
        //                             b.nombre AS nombreCliente,
        //                             b.correo AS correoCliente
        //                         FROM
        //                             servicio_pagar a,
        //                             clientes b
        //                         WHERE
        //                             a.cliente_id = b.id AND a.estado = ?
        //                         GROUP BY
        //                             a.cliente_id, b.nombre, b.correo', ['impago']);

        $usuario = Auth::user();

        $clientes = DB::select('SELECT
                                    COUNT(d.id) AS cantidad,
                                    a.cliente_id AS cliente_id,
                                    d.nombre AS nombreCliente,
                                    d.correo AS correoCliente,
                                    c.nombre AS nombreEmpresa
                                FROM
                                    servicio_pagar a,
                                    servicios b,
                                    empresas c,
                                    clientes d
                                WHERE
                                    a.cliente_id = d.id AND a.servicio_id = b.id AND b.empresa_id = c.id AND c.id = ? AND a.estado = ?
                                GROUP BY
                                    a.cliente_id, d.nombre, d.correo, c.nombre', [$usuario->empresa_id, 'impago']);
                                    

        // return $clientes;
        $i=0;
        

        foreach ($clientes as $valor) {
            $totalServicios=0;

            $serviciosImpagos [$i]['cliente_id'] =$valor->cliente_id;
            $serviciosImpagos [$i]['nombreCliente'] =$valor->nombreCliente;
            $serviciosImpagos [$i]['correoCliente'] =$valor->correoCliente;
            $serviciosImpagos[$i]['cantidad'] = $valor->cantidad;

            $serviciosImpagos[$i]['servicios'] = DB::select('SELECT
                                                                b.nombre AS nombreServicio,
                                                                a.cantidad AS cantidad,
                                                                a.precio AS precio,
                                                                a.precio * a.cantidad AS total,
                                                                a.created_at as fecha
                                                            FROM
                                                                servicio_pagar a,
                                                                servicios b
                                                            WHERE
                                                                a.servicio_id = b.id AND b.empresa_id = ? AND a.cliente_id = ? AND a.estado = ?', [$usuario->empresa_id, $valor->cliente_id,'impago']);

            foreach  ($serviciosImpagos[$i]['servicios'] as $datos){
                $totalServicios = $totalServicios + $datos->total;
            }

            $serviciosImpagos[$i]['total'] = $totalServicios;

            $i++;
        }

        // foreach  ($serviciosImpagos[1]['servicios'] as $datos){
        //     $totalServicios = $totalServicios + $datos->total;
        // }

        // return $serviciosImpagos;

        
        try {

            foreach ($serviciosImpagos as $key => $datos) {
                // echo $value['correoCliente'] . '<br>';
                
                // return (new NotificacionCuotaMail($datos))->render();
                // use App\Mail\NotificacionCuotaMail;
                // use Illuminate\Support\Facades\Mail;
    
                // return $datos;
                // return view('Correos.NotificacionMensualMail',['datos'=>$datos])->render();   
                // $correo = Mail::to($datos['correoCliente'])->send(new NotificacionTodosServiciosMail($datos));
                $correo =$datos['correoCliente'];
                EnviarEmailTodosServiciosImpagosJob::dispatch($correo,$datos);
                // echo $datos['correoCliente'];
                        
            }

            return redirect()->route('ServiciosImpagos')
            ->with('status','Mensaje Correcto');

        } catch (Exception $e) {
            return back()->withErrors(['Dirección de correo inválida']);
        }


    
    }
}
