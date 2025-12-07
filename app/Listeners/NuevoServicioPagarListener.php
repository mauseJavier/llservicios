<?php

namespace App\Listeners;

use App\Events\NuevoServicioPagarEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Mail\NotificacionCuotaMail;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class NuevoServicioPagarListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
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
                                a.cliente_id = b.id AND a.servicio_id = c.id AND a.id = ?', [$event->idServicioPagar]);


        $datos[0]->fechaServicio =  Carbon::parse($datos[0]->fechaServicio)->format('d-m-Y');

        // return $datos;


        try {

            if(empty($datos[0]->correoCliente)){
                return;
            }

            $correo = Mail::to($datos[0]->correoCliente)->send(new NotificacionCuotaMail($datos));

            
            $rutaArchivo = 'pruebaMail.txt';
                    $texto = json_encode($datos);

                    if (Storage::exists($rutaArchivo)) {
                        // El archivo existe
                        // echo "El archivo existe.";

                                //EDITANDO EL ARCHIVO

                    $contenidoActual = Storage::get($rutaArchivo);
                    $contenidoEditado = $contenidoActual . "\n" . $texto;
                    Storage::put($rutaArchivo, $contenidoEditado);

                    } else {
                        // El archivo no existe

                        Storage::disk('local')->put($rutaArchivo,$texto);

                    }


                    

        } catch (Exception $e) {
        
        }


    }
}
