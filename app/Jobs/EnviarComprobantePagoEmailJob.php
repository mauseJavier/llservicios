<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\ComprobantePagoMail;
use App\Models\ServicioPagar;
use Exception;

class EnviarComprobantePagoEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * El ID del servicio pagado
     *
     * @var int
     */
    public $idServicioPagar;

    /**
     * Datos adicionales del pago (opcional)
     *
     * @var array|null
     */
    public $datosPago;

    /**
     * Create a new job instance.
     *
     * @param int $idServicioPagar ID del servicio pagado
     * @param array|null $datosPago Datos adicionales del pago (opcional)
     */
    public function __construct(int $idServicioPagar, ?array $datosPago = null)
    {
        $this->idServicioPagar = $idServicioPagar;
        $this->datosPago = $datosPago;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validar que el ID esté definido
            if (!isset($this->idServicioPagar)) {
                throw new Exception('$idServicioPagar is not defined');
            }

            // Obtener los datos del servicio pagado desde la base de datos
            $servicioPagar = ServicioPagar::with(['cliente', 'servicio.empresa'])
                ->find($this->idServicioPagar);

            if (!$servicioPagar) {
                throw new Exception("No se encontró el servicio con ID: {$this->idServicioPagar}");
            }

            // Verificar que el cliente tenga correo
            if (!$servicioPagar->cliente || !$servicioPagar->cliente->correo) {
                throw new Exception("El cliente no tiene correo electrónico registrado");
            }

            // Preparar los datos para el correo y el PDF
            $datos = [
                'nombreCliente' => $servicioPagar->cliente->nombre ?? 'Sin nombre',
                'dniCliente' => $servicioPagar->cliente->dni ?? 'Sin DNI',
                'correoCliente' => $servicioPagar->cliente->correo,
                'nombreServicio' => $servicioPagar->servicio->nombre ?? 'Sin nombre',
                'nombreEmpresa' => $servicioPagar->servicio->empresa->nombre ?? 'Sin empresa',
                'cantidad' => $servicioPagar->cantidad ?? 1,
                'precioUnitario' => $servicioPagar->precio ?? 0,
                'total' => ($servicioPagar->cantidad ?? 1) * ($servicioPagar->precio ?? 0),
                'estado' => $servicioPagar->estado ?? 'pagado',
                'fechaPago' => $servicioPagar->updated_at ? $servicioPagar->updated_at->format('d/m/Y H:i') : date('d/m/Y H:i'),
                'comentario' => $servicioPagar->comentario ?? '',
                'mp_payment_id' => $servicioPagar->mp_payment_id ?? null,
            ];

            // Si se proporcionaron datos adicionales, mezclarlos
            if ($this->datosPago && is_array($this->datosPago)) {
                $datos = array_merge($datos, $this->datosPago);
            }

            // Enviar el correo con el comprobante PDF adjunto
            Mail::to($datos['correoCliente'])->send(new ComprobantePagoMail($datos));

        } catch (Exception $e) {
            // Registrar el error para debugging
            \Log::error("Error al enviar comprobante de pago por email: " . $e->getMessage(), [
                'idServicioPagar' => $this->idServicioPagar,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-lanzar la excepción para que Laravel maneje el fallo del Job
            throw $e;
        }
    }
}
