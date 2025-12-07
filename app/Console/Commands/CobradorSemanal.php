<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\NuevoServicioPagarEvent;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use Carbon\Carbon;

class CobradorSemanal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cobrador-semanal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera cobros semanales para servicios con frecuencia semanal vigentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando generación de cobros semanales...');
        
        $fechaHoy = Carbon::now();
        $cobrosGenerados = 0;
        
        // Obtener servicios semanales con sus clientes vigentes
        Servicio::where('tiempo', 'semana')
            ->with(['Clientes' => function($query) use ($fechaHoy) {
                $query->wherePivot('vencimiento', '>=', $fechaHoy);
            }])
            ->get()
            ->each(function($servicio) use ($fechaHoy, &$cobrosGenerados) {
                
                // Iterar sobre cada cliente del servicio
                $servicio->Clientes->each(function($cliente) use ($servicio, $fechaHoy, &$cobrosGenerados) {
                    
                    // Verificar si ya existe un cobro para esta semana
                    // $yaExiste = ServicioPagar::where('cliente_id', $cliente->id)
                    //     ->where('servicio_id', $servicio->id)
                    //     ->whereBetween('created_at', [
                    //         $fechaHoy->copy()->startOfWeek(),
                    //         $fechaHoy->copy()->endOfWeek()
                    //     ])
                    //     ->exists();

                    $yaExiste = false; // --- IGNORE ---
                    
                    if (!$yaExiste) {
                        // Calcular fecha de vencimiento
                        $diasVencimiento = $servicio->diasVencimiento ?? 10;
                        $fechaVencimiento = $fechaHoy->copy()->addDays($diasVencimiento);
                        
                        // Obtener precio del servicio y cantidad del pivot
                        $precio = $servicio->precio; // El precio siempre viene del servicio
                        $cantidad = $cliente->pivot->cantidad ?? 1;
                        
                        // Calcular el período de la semana (primer día de la semana actual)
                        $periodoServicio = $fechaHoy->copy()->startOfWeek()->format('Y-m-d');
                        
                        // Crear el cobro con todos los campos
                        $servicioPagar = ServicioPagar::create([
                            'cliente_id' => $cliente->id,
                            'servicio_id' => $servicio->id,
                            'precio' => $precio,
                            'cantidad' => $cantidad,
                            'estado' => 'impago',
                            'fecha_vencimiento' => $fechaVencimiento,
                            'periodo_servicio' => $periodoServicio,
                            'comentario' => "Cobro automático generado - Servicio semanal",
                            'mp_preference_id' => null,
                            'mp_payment_id' => null,
                        ]);
                        
                        // Disparar evento de nuevo servicio a pagar
                        // NuevoServicioPagarEvent::dispatch($servicioPagar->id);
                        
                        $cobrosGenerados++;
                        
                        $this->line("✅ Cobro creado: Cliente #{$cliente->id} - Servicio '{$servicio->nombre}' - Período: {$servicioPagar->periodo_servicio}");
                    }
                });
            });
        
        $this->info("✨ Proceso completado. Cobros generados: {$cobrosGenerados}");
        
        return Command::SUCCESS;
    }
}
