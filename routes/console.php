<?php

use App\Models\Empresa;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (App::environment('local')) {
    // Reconciliación de pagos de MercadoPago aprobados que no se procesaron por webhook/back_url
    Schedule::command('mp:reconciliar-pagos')->everyTenMinutes()->appendOutputTo(storage_path('logs/reconciliarPagos.log'));
} else {
    Schedule::command('app:cobrador-hora')->hourly()->appendOutputTo(storage_path('logs/tareasHora.log'));
    Schedule::command('app:cobrador-diario')->daily()->appendOutputTo(storage_path('logs/tareasDia.log'));
    Schedule::command('app:cobrador-semanal')->weekly()->appendOutputTo(storage_path('logs/tareasSemana.log'));
    Schedule::command('app:cobrador-mensual')->monthly()->appendOutputTo(storage_path('logs/tareasMes.log'));

    // Aplica el recargo por mora a los servicios impagos vencidos
    Schedule::command('app:aplicar-incremento-mora')->daily()->appendOutputTo(storage_path('logs/tareasMora.log'));

    // NOTIFICACIONES MENSUALES POR EMPRESA
    // Cada empresa define su día (dia_notificacion). El correo se envía y, a continuación,
    // el WhatsApp (mismo horario, uno detrás del otro).
    try {
        $empresas = Empresa::whereNotNull('dia_notificacion')->get();
    } catch (Throwable $e) {
        Log::error('No se pudo cargar el calendario de notificaciones por empresa', [
            'error' => $e->getMessage(),
        ]);
        $empresas = collect();
    }

    foreach ($empresas as $empresa) {
        Schedule::command('app:notificacion-mensual', [$empresa->id])
            ->monthlyOn($empresa->dia_notificacion, '13:00')
            ->appendOutputTo(storage_path('logs/notificacionMensual.log'));

        Schedule::command('app:notificacion-mensual-ws', [$empresa->id])
            ->monthlyOn($empresa->dia_notificacion, '13:00')
            ->appendOutputTo(storage_path('logs/notificacionMensualWS.log'));
    }

    // Reconciliación de pagos de MercadoPago aprobados que no se procesaron por webhook/back_url
    Schedule::command('mp:reconciliar-pagos')->everyTenMinutes()->appendOutputTo(storage_path('logs/reconciliarPagos.log'));
}
