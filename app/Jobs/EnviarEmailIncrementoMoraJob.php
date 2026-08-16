<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Mail\NotificacionIncrementoMoraMail;
use Illuminate\Support\Facades\Mail;

class EnviarEmailIncrementoMoraJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $idServicioPagar, public $datos)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!isset($this->idServicioPagar) || !is_array($this->datos)) {
            throw new \Exception('$idServicioPagar o $datos no definidos');
        }

        try {
            if (isset($this->datos['correoCliente']) && $this->datos['correoCliente'] != 'correo@correo.com') {
                $emailEnviado = false;

                // Intenta primero con SMTP
                try {
                    Mail::mailer('smtp')->to($this->datos['correoCliente'])->send(new NotificacionIncrementoMoraMail($this->datos));
                    \Log::info('Email de recargo por mora enviado vía SMTP a: ' . $this->datos['correoCliente']);
                    $emailEnviado = true;
                } catch (\Exception $e) {
                    \Log::warning('Fallo SMTP (recargo por mora): ' . $e->getMessage());

                    // Si SMTP falla, intenta con secundario
                    try {
                        Mail::mailer('secundario')->to($this->datos['correoCliente'])->send(new NotificacionIncrementoMoraMail($this->datos));
                        \Log::info('Email de recargo por mora enviado vía secundario a: ' . $this->datos['correoCliente']);
                        $emailEnviado = true;
                    } catch (\Exception $e2) {
                        \Log::error('Error en ambos mailers (recargo por mora) - SMTP: ' . $e->getMessage() . ' | Secundario: ' . $e2->getMessage());
                    }
                }
            } else {
                \Log::warning('No se envió el correo de recargo por mora porque el cliente no tiene un correo válido: ' . ($this->datos['nombreCliente'] ?? 'sin nombre'));
            }
        } catch (\Exception $e) {
            \Log::error('Error general en envío de email de recargo por mora: ' . $e->getMessage());
        }
    }
}