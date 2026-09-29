<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServicioPagar;
use Illuminate\Support\Facades\Log;
use App\Jobs\EnviarEmailIncrementoMoraJob;
use App\Jobs\EnviarWhatsAppJob;
use App\Services\MercadoPago\MercadoPagoLinkService;

class AplicarIncrementoMora extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:aplicar-incremento-mora';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplica el recargo por mora a los servicios impagos vencidos cuyo servicio tiene recargo configurado y el cliente tiene habilitados los recargos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando aplicación de recargos por mora...');

        $hoy = now()->toDateString();
        $recargosAplicados = 0;

        $serviciosPagar = ServicioPagar::with(['servicio', 'cliente'])
            ->join('servicios', 'servicio_pagar.servicio_id', '=', 'servicios.id')
            ->join('cliente_empresa', function ($join) {
                $join->on('cliente_empresa.cliente_id', '=', 'servicio_pagar.cliente_id')
                    ->on('cliente_empresa.empresa_id', '=', 'servicios.empresa_id');
            })
            ->select('servicio_pagar.*')
            ->where('servicio_pagar.estado', 'impago')
            ->where('servicio_pagar.incremento_mora_aplicado', false)
            ->whereNotNull('servicio_pagar.fecha_vencimiento')
            ->whereDate('servicio_pagar.fecha_vencimiento', '<', $hoy)
            ->whereNotNull('servicios.incremento_mora_tipo')
            ->where('cliente_empresa.aplicar_recargos', true)
            ->distinct()
            ->get();

        $this->info("📊 Se encontraron {$serviciosPagar->count()} servicios vencidos con recargo habilitado.");

        foreach ($serviciosPagar as $servicioPagar) {
            try {
                $servicio = $servicioPagar->servicio;
                $cliente = $servicioPagar->cliente;

                if (!$servicio || !$servicio->tieneIncrementoMora()) {
                    continue;
                }

                if (!$cliente || !$cliente->aplicaRecargos($servicio->empresa_id)) {
                    continue;
                }

                $totalOriginal = (float) $servicioPagar->precio * (float) $servicioPagar->cantidad;
                $precioOriginal = $servicioPagar->precio;
                $montoRecargo = $servicio->calcularIncrementoMora($servicioPagar);
                $totalConRecargo = $totalOriginal + $montoRecargo;

                if ($servicioPagar->cantidad > 0) {
                    $nuevoPrecio = round($totalConRecargo / (float) $servicioPagar->cantidad, 2);
                } else {
                    $nuevoPrecio = $servicioPagar->precio;
                }

                $comentarioBase = $servicioPagar->comentario ?? '';
                $detalleRecargo = $servicio->incremento_mora_tipo === 'fijo'
                    ? "Recargo por mora (fijo): \${$montoRecargo}"
                    : "Recargo por mora ({$servicio->incremento_mora_valor}%): \${$montoRecargo}";

                $servicioPagar->precio = $nuevoPrecio;
                $servicioPagar->incremento_mora_aplicado = true;
                $servicioPagar->comentario = $comentarioBase !== ''
                    ? $comentarioBase . ' | ' . $detalleRecargo
                    : $detalleRecargo;
                $servicioPagar->save();

                $recargosAplicados++;

                $this->line("✅ Recargo aplicado: ServicioPagar #{$servicioPagar->id} - Cliente '{$cliente->nombre}' - Total: \${$totalOriginal} → \${$totalConRecargo}");

                Log::info('Recargo por mora aplicado', [
                    'servicio_pagar_id' => $servicioPagar->id,
                    'cliente_id' => $cliente->id,
                    'servicio_id' => $servicio->id,
                    'tipo_recargo' => $servicio->incremento_mora_tipo,
                    'valor_recargo' => $servicio->incremento_mora_valor,
                    'total_original' => $totalOriginal,
                    'monto_recargo' => $montoRecargo,
                    'total_con_recargo' => $totalConRecargo,
                ]);

                $this->notificarRecargo($servicioPagar, $servicio, $cliente, $precioOriginal, $totalOriginal, $montoRecargo, $totalConRecargo);
            } catch (\Exception $e) {
                Log::error('Error al aplicar recargo por mora', [
                    'servicio_pagar_id' => $servicioPagar->id ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("❌ Error en ServicioPagar #{$servicioPagar->id}: " . $e->getMessage());
            }
        }

        $this->info("✨ Proceso completado. Recargos aplicados: {$recargosAplicados}");

        return Command::SUCCESS;
    }

    /**
     * Notificar al cliente por correo y WhatsApp cuando se aplica un recargo por mora.
     */
    private function notificarRecargo($servicioPagar, $servicio, $cliente, $precioOriginal, $totalOriginal, $montoRecargo, $totalConRecargo): void
    {
        try {
            $empresa = $servicio->empresa;

            // Link firmado de pago MercadoPago (individual; la app re-valida al hacer clic)
            $linkPago = MercadoPagoLinkService::urlEnlaceIndividual($servicioPagar->id);

            $datos = [
                'idServicioPagar' => $servicioPagar->id,
                'nombreCliente' => $cliente->nombre,
                'correoCliente' => $cliente->correo ?? '',
                'telefonoCliente' => $cliente->telefono ?? '',
                'nombreServicio' => $servicio->nombre,
                'cantidadServicio' => $servicioPagar->cantidad,
                'precioOriginal' => $precioOriginal,
                'totalOriginal' => $totalOriginal,
                'tipoRecargo' => $servicio->incremento_mora_tipo,
                'valorRecargo' => $servicio->incremento_mora_valor,
                'montoRecargo' => $montoRecargo,
                'totalConRecargo' => $totalConRecargo,
                'fechaVencimiento' => $servicioPagar->fecha_vencimiento ? date('d-m-Y', strtotime($servicioPagar->fecha_vencimiento)) : '',
                'nombreEmpresa' => $empresa->nombre ?? '',
                'empresaId' => $empresa->id ?? null,
                'linkPago' => $linkPago,
            ];

            EnviarEmailIncrementoMoraJob::dispatch($servicioPagar->id, $datos);

            if (!empty($cliente->telefono) && $empresa && !empty($empresa->instanciaWS) && !empty($empresa->tokenWS)) {
                $mensajeWhatsApp = $this->generarMensajeWhatsAppMora($datos);

                $buttons = [
                    ['type' => 'reply', 'displayText' => 'Informacion recibida', 'id' => 'info_recibida'],
                ];

                EnviarWhatsAppJob::dispatch([
                    'phoneNumber' => $cliente->telefono,
                    'message' => $mensajeWhatsApp,
                    'type' => 'buttons',
                    'additionalData' => [
                        'title' => 'Aviso de recargo por mora',
                        'footer' => $datos['nombreEmpresa'],
                        'buttons' => $buttons,
                    ],
                    'instanciaWS' => $empresa->instanciaWS,
                    'tokenWS' => $empresa->tokenWS,
                ]);
            }

            $this->line("📧 Notificaciones encoladas para ServicioPagar #{$servicioPagar->id}");
        } catch (\Exception $e) {
            Log::error('Error al notificar recargo por mora', [
                'servicio_pagar_id' => $servicioPagar->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generar mensaje de WhatsApp formateado para el aviso de recargo por mora.
     */
    private function generarMensajeWhatsAppMora(array $datos): string
    {
        $mensaje = "Hola *{$datos['nombreCliente']}*,\n\n";
        $mensaje .= "Le informamos desde *{$datos['nombreEmpresa']}* que tu servicio se encuentra *vencido* y se le aplicó un *recargo por mora*.\n\n";
        $mensaje .= "📋 *{$datos['nombreServicio']}*\n";
        $mensaje .= "   • Cantidad: {$datos['cantidadServicio']}\n";
        $mensaje .= "   • Total original: \$" . number_format($datos['totalOriginal'], 2) . "\n";
        $mensaje .= "   • Vencimiento: {$datos['fechaVencimiento']}\n\n";

        if ($datos['tipoRecargo'] === 'fijo') {
            $mensaje .= "💰 *Recargo fijo:* \$" . number_format($datos['montoRecargo'], 2) . "\n";
        } else {
            $mensaje .= "📈 *Recargo porcentual ({$datos['valorRecargo']}%):* \$" . number_format($datos['montoRecargo'], 2) . "\n";
        }

        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $mensaje .= "*NUEVO TOTAL: \$" . number_format($datos['totalConRecargo'], 2) . "*\n";
        $mensaje .= "━━━━━━━━━━━━━━━━━━━━━\n\n";

        if (!empty($datos['linkPago'])) {
            $mensaje .= "💳 Realice el pago aquí: {$datos['linkPago']}\n\n";
        } else {
            $mensaje .= "Realice el pago del servicio en la plataforma: " . env('APP_URL') . ".\n\n";
        }

        $mensaje .= "Cualquier consulta, no dudes en contactarnos.\n\n";
        $mensaje .= "_Mensaje automático - " . $datos['nombreEmpresa'] . "_";

        return $mensaje;
    }
}