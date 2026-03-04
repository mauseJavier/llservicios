<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

//NECESARIO 
use App\Mail\NotificacionTodosServiciosMail;
use Illuminate\Support\Facades\Mail;

class EnviarEmailTodosServiciosImpagosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $correo = 'mause@mause.com',public $datos)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $emailEnviado = false;
        
        // Intenta primero con SMTP
        try {
            Mail::mailer('smtp')->to($this->correo)->send(new NotificacionTodosServiciosMail($this->datos));
            \Log::info('Email enviado vía SMTP a: ' . $this->correo);
            $emailEnviado = true;
        } catch (\Exception $e) {
            \Log::warning('Fallo SMTP: ' . $e->getMessage());
            
            // Si SMTP falla, intenta con secundario
            try {
                Mail::mailer('secundario')->to($this->correo)->send(new NotificacionTodosServiciosMail($this->datos));
                \Log::info('Email enviado vía secundario a: ' . $this->correo);
                $emailEnviado = true;
            } catch (\Exception $e2) {
                \Log::error('Error en ambos mailers - SMTP: ' . $e->getMessage() . ' | Secundario: ' . $e2->getMessage());
            }
        }
    }
}
