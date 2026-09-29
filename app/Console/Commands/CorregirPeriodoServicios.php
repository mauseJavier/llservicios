<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServicioPagar;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CorregirPeriodoServicios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'servicios:corregir-periodo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa y corrige los periodos nulos en servicios por pagar';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando revisión de servicios con periodo nulo...');
        
        // Traer todos los servicios pagar con periodo nulo
        $serviciosPagar = ServicioPagar::whereNull('periodo_servicio')->get();
        
        // Cantidad de servicios a revisar
        $cantidad = $serviciosPagar->count();
        $this->info("📊 Cantidad de servicios a revisar: {$cantidad}");
        Log::info("Cantidad de servicios a revisar: {$cantidad}");
        
        if ($cantidad === 0) {
            $this->info('✅ No hay servicios con periodo nulo. Todo está correcto.');
            return 0;
        }
        
        // Crear barra de progreso
        $bar = $this->output->createProgressBar($cantidad);
        $bar->start();
        
        $corregidos = 0;
        
        foreach ($serviciosPagar as $servicioPagar) {
            // Obtener la fecha del primer día del mes de created_at
            $primerDiaMes = Carbon::parse($servicioPagar->created_at)->startOfMonth()->format('Y-m-d');
            
            // Actualizar el periodo_servicio
            $servicioPagar->periodo_servicio = $primerDiaMes;
            $servicioPagar->save();
            
            // Mostrar información de estos servicios
            $mensaje = "ServicioPagar ID: {$servicioPagar->id}, Cliente ID: {$servicioPagar->cliente_id}, Servicio ID: {$servicioPagar->servicio_id}, Fecha Vencimiento: {$servicioPagar->fecha_vencimiento}, Created At: {$servicioPagar->created_at}, Nuevo Periodo: {$primerDiaMes}";
            Log::info($mensaje);
            
            $corregidos++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Se corrigieron {$corregidos} servicios con periodo nulo.");
        $this->info('📝 Ver logs para detalles completos.');
        
        return 0;
    }
}
