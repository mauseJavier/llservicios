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
        Mail::to($this->correo)->send(new NotificacionTodosServiciosMail($this->datos));
    }
}
