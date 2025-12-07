<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Models\Pagos;

class RegistrarPagoListener
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
        // dd($event);

        Pagos::create([
            'id_servicio_pagar'=> $event->pago['idServicioPagar'],
            'id_usuario'=> $event->pago['idUsuario'],
            'importe'=> $event->pago['importe'],
            'forma_pago'=> $event->pago['forma_pago'],
            'forma_pago2'=> $event->pago['forma_pago2'] ?? null,
            'importe2'=> $event->pago['importe2'] ?? null,
            'comentario'=> $event->pago['comentario'],
        ]);

        // $table->unsignedBigInteger('id_servicio_pagar');
        // $table->unsignedBigInteger('id_usuario');
        // $table->double('importe',2);
    }
}
