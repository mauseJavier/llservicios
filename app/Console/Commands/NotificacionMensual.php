<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Mail\NotificacionTodosServiciosMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\EnviarWhatsAppJob;
use App\Models\Cliente;

class NotificacionMensual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notificacion-mensual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Para enviar notificacion via email y WhatsApp a los clientes con una lista de servicios adeudados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando notificación mensual de servicios impagos...');
        
        $clientes = DB::select('SELECT
                COUNT(*) AS cantidad,
                a.cliente_id AS cliente_id,
                b.nombre AS nombreCliente,
                b.correo AS correoCliente,
                b.telefono AS telefonoCliente
            FROM
                servicio_pagar a,
                clientes b
            WHERE
                a.cliente_id = b.id AND a.estado = ?
            GROUP BY
                a.cliente_id, b.nombre, b.correo, b.telefono', ['impago']);

        if (empty($clientes)) {
            $this->info('✅ No hay clientes con servicios impagos.');
            return;
        }

        $this->info("📊 Se encontraron " . count($clientes) . " clientes con servicios impagos");

        $serviciosImpagos = [];
        $i = 0;

        foreach ($clientes as $valor) {
            $totalServicios = 0;

            $serviciosImpagos[$i]['cliente_id'] = $valor->cliente_id;
            $serviciosImpagos[$i]['nombreCliente'] = $valor->nombreCliente;
            $serviciosImpagos[$i]['correoCliente'] = $valor->correoCliente;
            $serviciosImpagos[$i]['telefonoCliente'] = $valor->telefonoCliente;
            $serviciosImpagos[$i]['cantidad'] = $valor->cantidad;

            $serviciosImpagos[$i]['servicios'] = DB::select('SELECT
                                                b.nombre AS nombreServicio,
                                                a.cantidad AS cantidad,
                                                a.precio AS precio,
                                                a.precio * a.cantidad AS total,
                                                a.created_at as fecha
                                            FROM
                                                servicio_pagar a,
                                                servicios b
                                            WHERE
                                                a.servicio_id = b.id AND a.cliente_id = ? AND a.estado = ?', [$valor->cliente_id, 'impago']);

            foreach ($serviciosImpagos[$i]['servicios'] as $datos) {
                $totalServicios = $totalServicios + $datos->total;
            }

            $serviciosImpagos[$i]['total'] = $totalServicios;

            $i++;
        }

        // Contadores para el resumen
        $emailsEnviados = 0;
        $whatsappsEnviados = 0;
        $errores = 0;

        foreach ($serviciosImpagos as $key => $datos) {
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("📤 Procesando cliente: {$datos['nombreCliente']}");
            
            // Enviar email
            if (empty($datos['correoCliente'])) {
                $this->warn("  ⚠️  Cliente sin correo registrado");
            } else {
                try {
                    Mail::to($datos['correoCliente'])->send(new NotificacionTodosServiciosMail($datos));
                    $this->info("  ✅ Email enviado a: {$datos['correoCliente']}");
                    $emailsEnviados++;
                } catch (\Exception $e) {
                    $this->error("  ❌ Error enviando email: " . $e->getMessage());
                    $errores++;
                }
            }

            // Enviar WhatsApp si tiene teléfono
            if (!empty($datos['telefonoCliente'])) {
                try {
                    $mensajeWhatsApp = $this->generarMensajeWhatsApp($datos);
                    
                    // Despachar Job para envío asíncrono
                    EnviarWhatsAppJob::dispatch(
                        $datos['telefonoCliente'],
                        $mensajeWhatsApp,
                        'text'
                    )->delay(now()->addSeconds(5 * $key)); // Espaciar envíos
                    
                    $this->info("  ✅ WhatsApp programado para: {$datos['telefonoCliente']}");
                    $whatsappsEnviados++;
                } catch (\Exception $e) {
                    $this->error("  ❌ Error programando WhatsApp: " . $e->getMessage());
                    $errores++;
                }
            } else {
                $this->warn("  ⚠️  Cliente sin teléfono registrado");
            }
        }

        // Mostrar resumen
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 RESUMEN DE NOTIFICACIONES");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("  📧 Emails enviados: {$emailsEnviados}");
        $this->info("  📱 WhatsApps programados: {$whatsappsEnviados}");
        if ($errores > 0) {
            $this->error("  ⚠️  Errores encontrados: {$errores}");
        }
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Guardar log del proceso
        $this->guardarLog($serviciosImpagos, $emailsEnviados, $whatsappsEnviados, $errores);

        $this->info('✅ Proceso completado exitosamente');
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
        $mensaje .= "Por favor, regulariza tu situación a la brevedad.\n\n";
        $mensaje .= "Cualquier consulta, no dudes en contactarnos.\n\n";
        $mensaje .= "_Mensaje automático - " . config('app.name') . "_";
        
        return $mensaje;
    }

    /**
     * Guardar log del proceso
     * 
     * @param array $serviciosImpagos
     * @param int $emailsEnviados
     * @param int $whatsappsEnviados
     * @param int $errores
     * @return void
     */
    private function guardarLog(array $serviciosImpagos, int $emailsEnviados, int $whatsappsEnviados, int $errores): void
    {
        $datos = [
            'fecha' => date('Y-m-d H:i:s'),
            'resumen' => [
                'total_clientes' => count($serviciosImpagos),
                'emails_enviados' => $emailsEnviados,
                'whatsapps_programados' => $whatsappsEnviados,
                'errores' => $errores
            ],
            'datos' => $serviciosImpagos
        ];

        $rutaArchivo = 'logs/NotificacionMailMensual.txt';
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
