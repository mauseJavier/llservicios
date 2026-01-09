<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Jobs\EnviarWhatsAppJob;
use App\Models\Cliente;
use App\Models\Empresa;


use Illuminate\Support\Facades\Log;

class NotificacionMensualWS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notificacion-mensual-ws';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar notificación vía WhatsApp a los clientes con una lista de servicios adeudados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando notificación mensual WhatsApp de servicios impagos...');
        

        if( env('APP_ENV') == 'local'){

            // $empresas = Empresa::where('id', 2)->get();
            $empresas = Empresa::all();

            
        }else{
            
            $empresas = Empresa::all();
        }

        

        if (empty($empresas)) {
            $this->info('✅ No hay empresas con servicios impagos.');
            return;
        }

        $this->info("📊 Se encontraron " . count($empresas) . "");

        // $serviciosImpagos = [];
        // $i = 0;

        // foreach ($empresas as $valor) {
        //     $totalServicios = 0;

        //     $serviciosImpagos[$i]['cliente_id'] = $valor->cliente_id;
        //     $serviciosImpagos[$i]['nombreCliente'] = $valor->nombreCliente;
        //     $serviciosImpagos[$i]['correoCliente'] = $valor->correoCliente;
        //     $serviciosImpagos[$i]['telefonoCliente'] = $valor->telefonoCliente;
        //     $serviciosImpagos[$i]['cantidad'] = $valor->cantidad;

        //     $serviciosImpagos[$i]['servicios'] = DB::select('SELECT
        //                                         b.nombre AS nombreServicio,
        //                                         a.cantidad AS cantidad,
        //                                         a.precio AS precio,
        //                                         a.precio * a.cantidad AS total,
        //                                         a.created_at as fecha,
        //                                         c.nombre AS nombreEmpresa,
        //                                         c.id AS empresa_id
        //                                     FROM
        //                                         servicio_pagar a,
        //                                         servicios b,
        //                                         empresas c
        //                                     WHERE
        //                                         a.servicio_id = b.id AND b.empresa_id = c.id AND a.cliente_id = ? AND a.estado = ?', [$valor->cliente_id, 'impago']);

        //     foreach ($serviciosImpagos[$i]['servicios'] as $datos) {
        //         $totalServicios = $totalServicios + $datos->total;
        //     }

        //     $serviciosImpagos[$i]['total'] = $totalServicios;

        //     $i++;
        // }

        // // Contadores para el resumen
        // $whatsappsEnviados = 0;
        // $errores = 0;
        // $sinTelefono = 0;

        // foreach ($serviciosImpagos as $key => $datos) {
        //     $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        //     $this->info("📤 Procesando cliente: {$datos['nombreCliente']}");
            
        //     // Enviar WhatsApp si tiene teléfono
        //     if (!empty($datos['telefonoCliente'])) {
        //         try {
        //             $mensajeWhatsApp = $this->generarMensajeWhatsApp($datos);
                    
        //             // Despachar Job para envío asíncrono
        //             $datosJob = [
        //                 'phoneNumber' => $datos['telefonoCliente'],
        //                 'message' => $mensajeWhatsApp,
        //                 'type' => 'text',
        //                 'additionalData' => [],
        //                 'instanciaWS' => null,
        //                 'tokenWS' => null
        //             ];

        //             Log::info('Programando envío de WhatsApp', $datosJob);

        //             EnviarWhatsAppJob::dispatch($datosJob)->delay(now()->addSeconds(5 * $key)); // Espaciar envíos

        //             $this->info("  ✅ WhatsApp programado para: {$datos['telefonoCliente']}");
        //             $whatsappsEnviados++;
        //         } catch (\Exception $e) {
        //             $this->error("  ❌ Error programando WhatsApp: " . $e->getMessage());
        //             Log::error('Error programando WhatsApp', [
        //                 'cliente' => $datos['nombreCliente'],
        //                 'error' => $e->getMessage()
        //             ]);
        //             $errores++;
        //         }
        //     } else {
        //         $this->warn("  ⚠️  Cliente sin teléfono registrado");
        //         $sinTelefono++;
        //     }
        // }


        foreach($empresas as $empresa) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("🏢 Empresa: {$empresa->nombre}");
            $this->info("👥 Cantidad de clientes: " . $empresa->clientes->count());

            
            foreach($empresa->clientes as $cliente) {

                if( env('APP_ENV') == 'local'){

                    $this->info("  👤 Cliente: {$cliente->nombre} - Tel: {$cliente->telefono}");
                }

                // $serviciosImpagos = $cliente->serviciosImpagos();
                $serviciosImpagos = $cliente->serviciosImpagos()->whereHas('servicio', function($query) use ($empresa) {
                    $query->where('empresa_id', $empresa->id);
                })->get();

                if( env('APP_ENV') == 'local'){
                    $this->info(".......");

                    $this->info("  Servicios Impagos: {$serviciosImpagos->count()}");

                    foreach($serviciosImpagos as $servicio) {
                        $this->info("    - {$servicio->servicio->nombre} | Cantidad: {$servicio->cantidad} | Precio Unitario: {$servicio->precio} | Total: " . ($servicio->cantidad * $servicio->precio) . " | Fecha: " . $servicio->created_at->format('d/m/Y'));
                    }
                    $this->info(".......");

                }

                if($serviciosImpagos->count() > 0){

                    $datosCliente = [
                        'nombreCliente' => $cliente->nombre,
                        'cantidad' => $serviciosImpagos->count(),
                        'servicios' => [],
                        'total' => 0
                    ];

                    foreach($serviciosImpagos as $servicio) {
                        $datosCliente['servicios'][] = (object)[
                            'nombreServicio' => $servicio->servicio->nombre,
                            'cantidad' => $servicio->cantidad,
                            'precio' => $servicio->precio,
                            'total' => $servicio->cantidad * $servicio->precio,
                            'fecha' => $servicio->created_at->format('Y-m-d'),
                        ];

                        $datosCliente['total'] += $servicio->cantidad * $servicio->precio;
                    }

                    // Enviar WhatsApp si tiene teléfono
                    if (!empty($cliente->telefono)) {
                        try {
                            $mensajeWhatsApp = $this->generarMensajeWhatsApp($datosCliente);
                            
                            // Despachar Job para envío asíncrono
                            $datosJob = [
                                'phoneNumber' => $cliente->telefono,
                                'message' => $mensajeWhatsApp,
                                'type' => 'text',
                                'additionalData' => [],
                                'instanciaWS' => $empresa->instanciaWS,
                                'tokenWS' => $empresa->tokenWS
                            ];

                            if( env('APP_ENV') == 'local'){

                                Log::info('Programando envío de WhatsApp', $datosJob);
                            }
                            
                            EnviarWhatsAppJob::dispatch($datosJob);
                            
                            $this->info("  ✅ WhatsApp programado para: {$cliente->telefono}");
                        } catch (\Exception $e) {
                            $this->error("  ❌ Error programando WhatsApp: " . $e->getMessage());
                            Log::error('Error programando WhatsApp', [
                                'cliente' => $cliente->nombre,
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        $this->warn("  ⚠️  Cliente sin teléfono registrado");
                    }

                }
            }
        }

        // Mostrar resumen
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("✅ Proceso completado exitosamente");
    }

    /**
     * Generar mensaje de WhatsApp formateado
     * 
     * @param array $datos Datos del cliente y servicios
     * @return string
     */
    private function generarMensajeWhatsApp(array $datos): string
    {
        $mensaje = "⚠️ *RECORDATORIO DE SERVICIOS IMPAGOS*\n\n";
        $mensaje .= "Hola *{$datos['nombreCliente']}*,\n\n";
        $mensaje .= "Te recordamos que tienes *{$datos['cantidad']}* servicio(s) pendiente(s) de pago:\n\n";
        
        foreach ($datos['servicios'] as $servicio) {
            $mensaje .= "📋 *{$servicio->nombreServicio}*\n";
            $mensaje .= "   • Cantidad: {$servicio->cantidad}\n";
            $mensaje .= "   • Precio unitario: \${$servicio->precio}\n";
            $mensaje .= "   • Total: \$" . number_format($servicio->total, 2) . "\n";
            $mensaje .= "   • Fecha: " . date('d/m/Y', strtotime($servicio->fecha)) . "\n\n";
        }
        
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $mensaje .= "*TOTAL ADEUDADO: \$" . number_format($datos['total'], 2) . "*\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━\n\n";

        $mensaje .= "Realice el pago del servicio en la plataforma: " . env('APP_URL') . ".\n\n"; 
        
        $mensaje .= "Para registrarse, visite: " . env('APP_URL') . "/registro\n\n";
        
        $mensaje .= "Cualquier consulta, no dudes en contactarnos.\n\n";
        $mensaje .= "_Mensaje automático - " . config('app.name') . "_";
        
        return $mensaje;
    }

    /**
     * Guardar log del proceso
     * 
     * @param array $serviciosImpagos
     * @param int $whatsappsEnviados
     * @param int $sinTelefono
     * @param int $errores
     * @return void
     */
    private function guardarLog(array $serviciosImpagos, int $whatsappsEnviados, int $sinTelefono, int $errores): void
    {
        $datos = [
            'fecha' => date('Y-m-d H:i:s'),
            'resumen' => [
                'total_clientes' => count($serviciosImpagos),
                'whatsapps_programados' => $whatsappsEnviados,
                'sin_telefono' => $sinTelefono,
                'errores' => $errores
            ],
            'datos' => $serviciosImpagos
        ];

        $rutaArchivo = 'logs/NotificacionWsMensual.txt';
        $texto = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        try {
            if (Storage::exists($rutaArchivo)) {
                Storage::append($rutaArchivo, "\n" . $texto);
            } else {
                Storage::disk('local')->put($rutaArchivo, $texto);
            }
            
            $this->info("📝 Log guardado en: storage/app/{$rutaArchivo}");
        } catch (\Exception $e) {
            $this->error("❌ Error guardando log: " . $e->getMessage());
        }
    }
}
