<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// NECESARIO
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class EnviarWhatsAppNuevoServicioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $idServicioPagar;
    public $instanciaWS;
    public $tokenWS;

    /**
     * Create a new job instance.
     */
    public function __construct($idServicioPagar, string $instanciaWS = null, string $tokenWS = null)
    {
        $this->idServicioPagar = $idServicioPagar;
        $this->instanciaWS = $instanciaWS;
        $this->tokenWS = $tokenWS;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validar que $idServicioPagar esté definido
            if (!isset($this->idServicioPagar)) {
                throw new \Exception('$idServicioPagar no está definido');
            }

            // Obtener los datos del servicio a pagar
            $datos = DB::select('SELECT
                        b.nombre AS nombreCliente,
                        b.telefono AS telefonoCliente,
                        c.nombre AS nombreServicio,
                        a.cantidad AS cantidadServicio,
                        a.precio AS precioServicio,
                        a.created_at AS fechaServicio,
                        d.nombre AS nombreEmpresa
                    FROM
                        servicio_pagar a,
                        clientes b,
                        servicios c,
                        empresas d
                    WHERE
                        a.cliente_id = b.id 
                        AND a.servicio_id = c.id 
                        AND c.empresa_id = d.id
                        AND a.id = ?', [$this->idServicioPagar]);

            // Verificar que se encontraron datos
            if (empty($datos)) {
                Log::warning('WhatsApp Job - No se encontraron datos para el servicio', [
                    'idServicioPagar' => $this->idServicioPagar
                ]);
                return;
            }

            $datosServicio = $datos[0];

            // Verificar que el cliente tenga teléfono
            if (empty($datosServicio->telefonoCliente)) {
                Log::info('WhatsApp Job - Cliente sin número de teléfono', [
                    'idServicioPagar' => $this->idServicioPagar,
                    'cliente' => $datosServicio->nombreCliente
                ]);
                return;
            }

            // Formatear la fecha
            $fechaFormateada = Carbon::parse($datosServicio->fechaServicio)->format('d/m/Y');

            Log::info('WhatsApp Job - Enviando notificación de nuevo servicio', [
                'telefono' => $datosServicio->telefonoCliente,
                'cliente' => $datosServicio->nombreCliente,
                'servicio' => $datosServicio->nombreServicio
            ]);

            // Instanciar el servicio de WhatsApp
            $whatsappService = new WhatsAppService($this->instanciaWS, $this->tokenWS);

            $total = number_format($datosServicio->precioServicio * $datosServicio->cantidadServicio, 2, ',', '.');

            $resultado = $whatsappService->sendButtons(
                $datosServicio->telefonoCliente,
                '📢 Nuevo Servicio Registrado',
                "Hola {$datosServicio->nombreCliente}, se ha registrado un nuevo servicio a su nombre:\n\n• Servicio: {$datosServicio->nombreServicio}\n• Cantidad: {$datosServicio->cantidadServicio}\n• Precio unitario: \$" . number_format($datosServicio->precioServicio, 2, ',', '.') . "\n• Fecha: {$fechaFormateada}\n\n💰 Total a pagar: \${$total}",
                'Gracias por su atención',
                [
                    ['type' => 'reply', 'displayText' => '💳 Pagar ahora', 'id' => 'pagar_servicio'],
                    ['type' => 'reply', 'displayText' => '📞 Contactar', 'id' => 'contactar'],
                    ['type' => 'reply', 'displayText' => 'Gracias por la información', 'id' => 'info_recibida'],
                ]
            );

            if ($resultado['success']) {
                Log::info('WhatsApp Job - Notificación de nuevo servicio enviada exitosamente', [
                    'telefono' => $datosServicio->telefonoCliente,
                    'cliente' => $datosServicio->nombreCliente,
                    'servicio' => $datosServicio->nombreServicio
                ]);
            } else {
                Log::error('WhatsApp Job - Error al enviar notificación de nuevo servicio', [
                    'telefono' => $datosServicio->telefonoCliente,
                    'error' => $resultado['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp Job - Excepción al enviar notificación de nuevo servicio', [
                'idServicioPagar' => $this->idServicioPagar,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Construir el mensaje de WhatsApp para nuevo servicio
     */
    private function construirMensaje($datos, string $fechaFormateada): string
    {
        $nombreCliente = $datos->nombreCliente;
        $nombreEmpresa = $datos->nombreEmpresa ?? 'nuestra empresa';
        $nombreServicio = $datos->nombreServicio;
        $cantidad = $datos->cantidadServicio;
        $precioUnitario = number_format($datos->precioServicio, 2, ',', '.');
        $total = number_format($datos->precioServicio * $cantidad, 2, ',', '.');

        $mensaje = "📢 *Nuevo Servicio Registrado*\n\n";
        $mensaje .= "Hola *{$nombreCliente}*,\n\n";
        $mensaje .= "Le informamos desde *{$nombreEmpresa}* que se ha registrado un nuevo servicio a su nombre:\n\n";
        
        $mensaje .= "📋 *Detalle del servicio:*\n";
        $mensaje .= "   • Servicio: *{$nombreServicio}*\n";
        $mensaje .= "   • Cantidad: {$cantidad}\n";
        $mensaje .= "   • Precio unitario: \${$precioUnitario}\n";
        $mensaje .= "   • Fecha de registro: {$fechaFormateada}\n\n";
        
        $mensaje .= "💰 *Total a pagar: \${$total}*\n\n";
                
        $mensaje .= "Realice el pago del servicio en la plataforma: " . env('APP_URL') . ".\n\n"; 
        
        $mensaje .= "Para registrarse, visite: " . env('APP_URL') . "/registro\n\n";


        $mensaje .= "Si tiene alguna consulta, no dude en contactarnos.\n\n";
        $mensaje .= "Gracias por su atención. 🙏";

        return $mensaje;
    }
}
