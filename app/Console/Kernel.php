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
            $schedule->command('app:cobrador-servicios')->everyMinute();
            $schedule->command('app:cobrador-mensual')->everyMinute()->appendOutputTo(storage_path('logs/tareasMensual.log'));
        }else{

            //¡¡¡¡¡¡¡¡¡¡¡¡¡QUITAR PARA PRUDUCCION¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¿
            $schedule->command('app:cobrador-mensual')->everyMinute()->appendOutputTo(storage_path('logs/tareasMensual.log'));
            //¡¡¡¡¡¡¡¡¡¡¡¡¡QUITAR PARA PRUDUCCION¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¿

            $schedule->command('app:cobrador-mensual')->monthly()->appendOutputTo(storage_path('logs/tareasMensual.log'));

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
