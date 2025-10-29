<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\NuevoServicioPagarEvent;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use Carbon\Carbon;

class CobradorMensual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cobrador-mensual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera cobros mensuales para servicios con frecuencia mensual vigentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando generación de cobros mensuales...');
        
        $fechaHoy = Carbon::now();
        $cobrosGenerados = 0;
        
        // Obtener servicios mensuales con sus clientes vigentes
        Servicio::where('tiempo', 'mes')
            ->with(['Clientes' => function($query) use ($fechaHoy) {
                $query->wherePivot('vencimiento', '>=', $fechaHoy);
            }])
            ->get()
            ->each(function($servicio) use ($fechaHoy, &$cobrosGenerados) {
                
                // Iterar sobre cada cliente del servicio
                $servicio->Clientes->each(function($cliente) use ($servicio, $fechaHoy, &$cobrosGenerados) {
                    
                    // Verificar si ya existe un cobro para este mes
                    // $yaExiste = ServicioPagar::where('cliente_id', $cliente->id)
                    //     ->where('servicio_id', $servicio->id)
                    //     ->whereMonth('created_at', $fechaHoy->month)
                    //     ->whereYear('created_at', $fechaHoy->year)
                    //     ->exists();

                    $yaExiste = false; // --- IGNORE ---
                    
                    if (!$yaExiste) {
                        // Calcular fecha de vencimiento
                        $diasVencimiento = $servicio->diasVencimiento ?? 10;
                        $fechaVencimiento = $fechaHoy->copy()->addDays($diasVencimiento);
                        
                        // Obtener precio del servicio y cantidad del pivot
                        $precio = $servicio->precio; // El precio siempre viene del servicio
                        $cantidad = $cliente->pivot->cantidad ?? 1;
                        
                        // Crear el cobro con todos los campos
                        $servicioPagar = ServicioPagar::create([
                            'cliente_id' => $cliente->id,
                            'servicio_id' => $servicio->id,
                            'precio' => $precio,
                            'cantidad' => $cantidad,
                            'estado' => 'impago',
                            'fecha_vencimiento' => $fechaVencimiento,
                            'periodo_servicio' => $fechaHoy->format('Y-m-01'), // Primer día del mes actual
                            'comentario' => "Cobro automático generado - Servicio mensual",
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
