<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Auth\Events\Login;

class UserLoggedIn
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
    public function handle(Login $event): void
    {
        // Obtenemos el usuario que inició sesión
        $user = $event->user;

        // Actualizamos la columna last_login con la fecha y hora actual
        $user->last_login = now();
        $user->save();
    }
}
