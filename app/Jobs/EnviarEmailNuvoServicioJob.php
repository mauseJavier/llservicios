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
                    b.correo as correoCliente,
                    d.nombre AS nombreEmpresa
                FROM
                    servicio_pagar a,
                    clientes b,
                    servicios c,
                    empresas d
                WHERE
                    a.cliente_id = b.id AND a.servicio_id = c.id AND c.empresa_id = d.id AND a.id = ?', [$this->idServicioPagar]);


            $datos[0]->fechaServicio =  Carbon::parse($datos[0]->fechaServicio)->format('d-m-Y');

            // return $datos;


            try {

                // return (new NotificacionCuotaMail($datos))->render();
                // use App\Mail\NotificacionCuotaMail;
                // use Illuminate\Support\Facades\Mail;
                if (isset($datos[0]->correoCliente) && $datos[0]->correoCliente != 'correo@correo.com') {
                    $emailEnviado = false;
                    
                    // Intenta primero con SMTP
                    try {
                        Mail::mailer('smtp')->to($datos[0]->correoCliente)->send(new NotificacionCuotaMail($datos));
                        \Log::info('Email enviado vía SMTP a: ' . $datos[0]->correoCliente);
                        $emailEnviado = true;
                    } catch (\Exception $e) {
                        \Log::warning('Fallo SMTP: ' . $e->getMessage());
                        
                        // Si SMTP falla, intenta con secundario
                        try {
                            Mail::mailer('secundario')->to($datos[0]->correoCliente)->send(new NotificacionCuotaMail($datos));
                            \Log::info('Email enviado vía secundario a: ' . $datos[0]->correoCliente);
                            $emailEnviado = true;
                        } catch (\Exception $e2) {
                            \Log::error('Error en ambos mailers - SMTP: ' . $e->getMessage() . ' | Secundario: ' . $e2->getMessage());
                        }
                    }
                }else {
                    \Log::warning('No se envió el correo porque el cliente no tiene un correo válido: ' . $datos[0]->nombreCliente);    
                }

            } catch (Exception $e) {
                // Log the exception or handle it as needed
                \Log::error('Error general en envío de email: ' . $e->getMessage());    
            }

    }
}
