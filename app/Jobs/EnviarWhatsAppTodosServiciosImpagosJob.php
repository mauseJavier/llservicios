<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\MercadoPago\MercadoPagoLinkService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class EnviarWhatsAppTodosServiciosImpagosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $telefono;
    public $datos;
    public $instanciaWS;
    public $tokenWS;

    /**
     * Create a new job instance.
     */
    public function __construct(string $telefono, array $datos, string $instanciaWS = null, string $tokenWS = null)
    {
        $this->telefono = $telefono;
        $this->datos = $datos;
        $this->instanciaWS = $instanciaWS;
        $this->tokenWS = $tokenWS;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('WhatsApp Job - Iniciando envío de notificación de servicios impagos', [
                'telefono' => $this->telefono,
                'cliente' => $this->datos['nombreCliente'] ?? 'N/A'
            ]);

            // Instanciar el servicio de WhatsApp
            $whatsappService = new WhatsAppService($this->instanciaWS, $this->tokenWS);

            $nombreCliente = $this->datos['nombreCliente'];
            $nombreEmpresa = $this->datos['nombreEmpresa'] ?? 'nuestra empresa';
            $total = number_format($this->datos['total'], 2, ',', '.');

            // Link firmado de pago agrupado (la app resuelve los impagos al hacer clic)
            $linkPago = null;
            if (!empty($this->datos['cliente_id']) && !empty($this->datos['empresa_id'])) {
                $linkPago = MercadoPagoLinkService::urlEnlaceCliente(
                    (int) $this->datos['cliente_id'],
                    (int) $this->datos['empresa_id']
                );
            }

            $mensaje = "Hola {$nombreCliente}, le informamos desde {$nombreEmpresa} que tiene {$this->datos['cantidad']} servicio(s) pendiente(s) de pago.\n\n💰 Total adeudado: \${$total}\n\nPor favor, regularice su situación a la brevedad posible.";

            if ($linkPago) {
                $mensaje .= "\n\n💳 Realice el pago aquí: {$linkPago}";
            }

            $resultado = $whatsappService->sendButtons(
                $this->telefono,
                '🔔 Servicios Pendientes',
                $mensaje,
                'Gracias por su atención',
                [
                    ['type' => 'reply', 'displayText' => 'Información Recibida', 'id' => 'info_recibida'],
                ]
            );

            if ($resultado['success']) {
                Log::info('WhatsApp Job - Mensaje enviado exitosamente', [
                    'telefono' => $this->telefono,
                    'cliente' => $this->datos['nombreCliente']
                ]);
            } else {
                Log::error('WhatsApp Job - Error al enviar mensaje', [
                    'telefono' => $this->telefono,
                    'error' => $resultado['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp Job - Excepción al enviar mensaje', [
                'telefono' => $this->telefono,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Construir el mensaje de WhatsApp con los servicios adeudados
     */
    private function construirMensaje(): string
    {
        $nombreCliente = $this->datos['nombreCliente'];
        $nombreEmpresa = $this->datos['nombreEmpresa'] ?? 'nuestra empresa';
        $cantidad = $this->datos['cantidad'];
        $total = number_format($this->datos['total'], 2, ',', '.');

        $mensaje = "🔔 *Notificación de Servicios Pendientes*\n\n";
        $mensaje .= "Hola *{$nombreCliente}*,\n\n";
        $mensaje .= "Le informamos desde *{$nombreEmpresa}* que tiene *{$cantidad}* servicio(s) pendiente(s) de pago:\n\n";

        // Agregar detalle de cada servicio
        foreach ($this->datos['servicios'] as $index => $servicio) {
            $subtotal = number_format($servicio->total, 2, ',', '.');
            $fecha = date('d/m/Y', strtotime($servicio->fecha));
            
            $mensaje .= "📌 *Servicio " . ($index + 1) . ":*\n";
            $mensaje .= "   • Nombre: {$servicio->nombreServicio}\n";
            $mensaje .= "   • Cantidad: {$servicio->cantidad}\n";
            $mensaje .= "   • Precio unitario: \${$servicio->precio}\n";
            $mensaje .= "   • Subtotal: \${$subtotal}\n";
            $mensaje .= "   • Fecha: {$fecha}\n\n";
        }

        $mensaje .= "💰 *Total adeudado: \${$total}*\n\n";
        $mensaje .= "Por favor, regularice su situación a la brevedad posible.\n\n";
        $mensaje .= "Ante cualquier consulta, no dude en contactarnos.\n\n";
        $mensaje .= "Gracias por su atención. 🙏";

        return $mensaje;
    }
}
