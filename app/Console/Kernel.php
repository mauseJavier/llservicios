<?php

namespace App\Console;
use Illuminate\Support\Facades\App;
 

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        if (App::environment('local')) {
            // The environment is local
            // $schedule->command('app:cobrador-servicios')->everyMinute();
            // $schedule->command('app:notificacion-mensual')->everyMinute()->appendOutputTo(storage_path('logs/notificacionMensual.log'));
            // $schedule->command('app:cobrador-mensual')->everyMinute()->appendOutputTo(storage_path('logs/tareasMensualDesarrollo.log'));

            // agregar cobrador por minuto
            // $schedule->command('app:cobrador-minuto')->everyMinute()->appendOutputTo(storage_path('logs/tareasMinuto.log'));

        }else{

            //¡¡¡¡¡¡¡¡¡¡¡¡¡QUITAR PARA PRUDUCCION¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¿
            // $schedule->command('app:cobrador-mensual')->everyMinute()->appendOutputTo(storage_path('logs/tareasMensualDesarrollo.log'));
            //¡¡¡¡¡¡¡¡¡¡¡¡¡QUITAR PARA PRUDUCCION¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¿

            $schedule->command('app:cobrador-hora')->hourly()->appendOutputTo(storage_path('logs/tareasHora.log'));
            $schedule->command('app:cobrador-diario')->daily()->appendOutputTo(storage_path('logs/tareasDia.log'));
            $schedule->command('app:cobrador-semanal')->weekly()->appendOutputTo(storage_path('logs/tareasSemana.log'));
            $schedule->command('app:cobrador-mensual')->monthly()->appendOutputTo(storage_path('logs/tareasMes.log'));

            //NOTIFICACION MENSUAL SE EJECUTA 2 Y 7 A LAS 13 
            $schedule->command('app:notificacion-mensual')->twiceMonthly(2, 7, '13:00')->appendOutputTo(storage_path('logs/notificacionMensual.log'));

        }
        
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
