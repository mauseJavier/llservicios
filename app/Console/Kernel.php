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
            
            // Notificación WhatsApp cada minuto para desarrollo
            // $schedule->command('app:notificacion-mensual-ws')->everyMinute()->appendOutputTo(storage_path('logs/notificacionMensualWS.log'));

            // agregar cobrador por minuto
            // $schedule->command('app:cobrador-minuto')->everyMinute()->appendOutputTo(storage_path('logs/tareasMinuto.log'));

            //Reconciliación de pagos de MercadoPago aprobados que no se procesaron por webhook/back_url
            $schedule->command('mp:reconciliar-pagos')->everyTenMinutes()->appendOutputTo(storage_path('logs/reconciliarPagos.log'));

        }else{

            //¡¡¡¡¡¡¡¡¡¡¡¡¡QUITAR PARA PRUDUCCION¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¿
            // $schedule->command('app:cobrador-mensual')->everyMinute()->appendOutputTo(storage_path('logs/tareasMensualDesarrollo.log'));
            //¡¡¡¡¡¡¡¡¡¡¡¡¡QUITAR PARA PRUDUCCION¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¿

            $schedule->command('app:cobrador-hora')->hourly()->appendOutputTo(storage_path('logs/tareasHora.log'));
            $schedule->command('app:cobrador-diario')->daily()->appendOutputTo(storage_path('logs/tareasDia.log'));
            $schedule->command('app:cobrador-semanal')->weekly()->appendOutputTo(storage_path('logs/tareasSemana.log'));
            $schedule->command('app:cobrador-mensual')->monthly()->appendOutputTo(storage_path('logs/tareasMes.log'));

            //Aplica el recargo por mora a los servicios impagos vencidos
            $schedule->command('app:aplicar-incremento-mora')->daily()->appendOutputTo(storage_path('logs/tareasMora.log'));

            //NOTIFICACIONES MENSUALES POR EMPRESA
            //Cada empresa define su día (dia_notificacion). El correo se envía y, a continuación,
            //el WhatsApp (mismo horario, uno detrás del otro).
            try {
                $empresas = \App\Models\Empresa::whereNotNull('dia_notificacion')->get();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('No se pudo cargar el calendario de notificaciones por empresa', [
                    'error' => $e->getMessage(),
                ]);
                $empresas = collect();
            }

            foreach ($empresas as $empresa) {
                $schedule->command('app:notificacion-mensual', [$empresa->id])
                    ->monthlyOn($empresa->dia_notificacion, '13:00')
                    ->appendOutputTo(storage_path('logs/notificacionMensual.log'));

                $schedule->command('app:notificacion-mensual-ws', [$empresa->id])
                    ->monthlyOn($empresa->dia_notificacion, '13:00')
                    ->appendOutputTo(storage_path('logs/notificacionMensualWS.log'));
            }

            //Reconciliación de pagos de MercadoPago aprobados que no se procesaron por webhook/back_url
            $schedule->command('mp:reconciliar-pagos')->everyTenMinutes()->appendOutputTo(storage_path('logs/reconciliarPagos.log'));

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
