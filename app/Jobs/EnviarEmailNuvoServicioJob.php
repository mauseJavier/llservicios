<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


//NECESARIO
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Mail\NotificacionCuotaMail;
use App\Mail\NotificacionTodosServiciosMail;
use Illuminate\Support\Facades\Mail;

class EnviarEmailNuvoServicioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    /**
     * Create a new job instance.
     */
    public function __construct(public $idServicioPagar)
    {
        
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

                // Ensure that $idServicioPagar is defined
                if (!isset($this->idServicioPagar)) {
                    throw new Exception('$idServicioPagar is not defined');
                }


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
                    a.cliente_id = b.id AND a.servicio_id = c.id AND a.id = ?', [$this->idServicioPagar]);


            $datos[0]->fechaServicio =  Carbon::parse($datos[0]->fechaServicio)->format('d-m-Y');

            // return $datos;


            try {

            // return (new NotificacionCuotaMail($datos))->render();
            // use App\Mail\NotificacionCuotaMail;
            // use Illuminate\Support\Facades\Mail;
            $correo = Mail::to($datos[0]->correoCliente)->send(new NotificacionCuotaMail($datos));


            } catch (Exception $e) {
            
            }

    }
}
