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
use App\Jobs\EnviarWhatsAppTodosServiciosImpagosJob;
use App\Jobs\EnviarWhatsAppNuevoServicioJob;


class EnviarCorreoController extends Controller
{

    public function NotificacionNuevoServicio($idServicioPagar){

        $usuario = Auth::user();
        $empresa = \App\Models\Empresa::find($usuario->empresa_id);


        $instanciaWS = $empresa->instanciaWS ?? null;
        $tokenWS = $empresa->tokenWS ?? null;

        // Enviar correo electrónico
        EnviarEmailNuvoServicioJob::dispatch($idServicioPagar);

        // Enviar WhatsApp
        EnviarWhatsAppNuevoServicioJob::dispatch($idServicioPagar, $instanciaWS, $tokenWS);

        return redirect()->route('ServiciosImpagos')
        ->with('status','Notificaciones enviadas correctamente (Email y WhatsApp)');



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


            // LLAMADA A LA FUNCION DE WHATSAPP
            $this->NotificacionWhatsAppTodosServiciosImpagos();

            return redirect()->route('ServiciosImpagos')
            ->with('status','Mensaje Correcto');

        } catch (Exception $e) {
            return back()->withErrors(['Dirección de correo inválida']);
        }


    
    }

    /**
     * Enviar notificación de servicios impagos por WhatsApp a todos los clientes
     */
    public function NotificacionWhatsAppTodosServiciosImpagos()
    {
        $usuario = Auth::user();

        $empresa = \App\Models\Empresa::find($usuario->empresa_id);



        $instanciaWS = $empresa->instanciaWS;
        $tokenWS = $empresa->tokenWS;

        // Obtener clientes con servicios impagos de la empresa del usuario logueado
        $clientes = DB::select('SELECT
                                    COUNT(d.id) AS cantidad,
                                    a.cliente_id AS cliente_id,
                                    d.nombre AS nombreCliente,
                                    d.telefono AS telefonoCliente,
                                    c.nombre AS nombreEmpresa
                                FROM
                                    servicio_pagar a,
                                    servicios b,
                                    empresas c,
                                    clientes d
                                WHERE
                                    a.cliente_id = d.id 
                                    AND a.servicio_id = b.id 
                                    AND b.empresa_id = c.id 
                                    AND c.id = ? 
                                    AND a.estado = ?
                                    AND d.telefono IS NOT NULL
                                    AND d.telefono != ""
                                GROUP BY
                                    a.cliente_id, d.nombre, d.telefono, c.nombre', 
                                    [$usuario->empresa_id, 'impago']);

        $i = 0;
        $serviciosImpagos = [];
        $clientesNotificados = 0;
        $clientesSinTelefono = 0;

        foreach ($clientes as $valor) {
            $totalServicios = 0;

            $serviciosImpagos[$i]['cliente_id'] = $valor->cliente_id;
            $serviciosImpagos[$i]['nombreCliente'] = $valor->nombreCliente;
            $serviciosImpagos[$i]['telefonoCliente'] = $valor->telefonoCliente;
            $serviciosImpagos[$i]['nombreEmpresa'] = $valor->nombreEmpresa;
            $serviciosImpagos[$i]['cantidad'] = $valor->cantidad;

            // Obtener los servicios impagos del cliente
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
                                                                a.servicio_id = b.id 
                                                                AND b.empresa_id = ? 
                                                                AND a.cliente_id = ? 
                                                                AND a.estado = ?', 
                                                            [$usuario->empresa_id, $valor->cliente_id, 'impago']);

            // Calcular el total
            foreach ($serviciosImpagos[$i]['servicios'] as $datos) {
                $totalServicios = $totalServicios + $datos->total;
            }

            $serviciosImpagos[$i]['total'] = $totalServicios;

            $i++;
        }

        try {
            // Enviar WhatsApp a cada cliente que tenga teléfono
            foreach ($serviciosImpagos as $datos) {
                if (!empty($datos['telefonoCliente'])) {
                    EnviarWhatsAppTodosServiciosImpagosJob::dispatch($datos['telefonoCliente'], $datos, $instanciaWS, $tokenWS);
                    $clientesNotificados++;
                } else {
                    $clientesSinTelefono++;
                }
            }

            return redirect()->route('ServiciosImpagos')
                ->with('status', "WhatsApp enviados correctamente. {$clientesNotificados} cliente(s) notificado(s).");

        } catch (\Exception $e) {
            return back()->withErrors(['Error al enviar mensajes de WhatsApp: ' . $e->getMessage()]);
        }
    }
}
