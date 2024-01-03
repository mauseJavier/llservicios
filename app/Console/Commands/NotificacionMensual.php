<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


use App\Mail\NotificacionTodosServiciosMail;
use Illuminate\Support\Facades\Mail;

class NotificacionMensual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notificacion-mensual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Para enviar notificacion via email a los clientes con una lista de servicios adeudados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clientes = DB::select('SELECT
                COUNT(*) AS cantidad,
                a.cliente_id AS cliente_id,
                b.nombre AS nombreCliente,
                b.correo AS correoCliente
            FROM
                servicio_pagar a,
                clientes b
            WHERE
                a.cliente_id = b.id AND a.estado = ?
            GROUP BY
                a.cliente_id, b.nombre, b.correo', ['impago']);


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
                                            a.servicio_id = b.id AND a.cliente_id = ? AND a.estado = ?', [$valor->cliente_id,'impago']);

        foreach  ($serviciosImpagos[$i]['servicios'] as $datos){
        $totalServicios = $totalServicios + $datos->total;
        }

        $serviciosImpagos[$i]['total'] = $totalServicios;

        $i++;
        }


        foreach ($serviciosImpagos as $key => $datos) {
            // echo $value['correoCliente'] . '<br>';
            // return view('Correos/NorificacionMensualMail',['datos'=>$serviciosImpagos[1]])->render();   
            // return (new NotificacionCuotaMail($datos))->render();
            // use App\Mail\NotificacionCuotaMail;
            // use Illuminate\Support\Facades\Mail;
            $correo = Mail::to($datos['correoCliente'])->send(new NotificacionTodosServiciosMail($datos));
                    
        }

    



        //AK TERNIMA EL PROCESO Y REALIZA UN LOG PARA SABER QUE PASO 

        $datos = array('fecha'=>date('Y-m-d H:i:s'),
                        'datos'=> $serviciosImpagos);

        $rutaArchivo = 'logs/NotificacionMailMensual.txt';
        $texto = json_encode($datos);

        if (Storage::exists($rutaArchivo)) {
            // El archivo existe
            // echo "El archivo existe.";

        //EDITANDO EL ARCHIVO
        // $contenidoActual = Storage::get($rutaArchivo);
        // $contenidoEditado = $contenidoActual . "\n" . $texto;
        // Storage::put($rutaArchivo, $contenidoEditado);

        Storage::append($rutaArchivo, $texto);

        } else {
            // El archivo no existe
            // echo "El archivo no existe.";            

            Storage::disk('local')->put($rutaArchivo,$texto);

        }



    }
}
