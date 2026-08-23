<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcesarPagoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 5;
    public $timeout = 120;

    protected ?string $phoneNumber;
    protected string $mensajeTexto;
    protected ?string $instanciaWS;
    protected ?string $tokenWS;
    protected array $datosPDF;
    protected ?array $datosCorreo;
    protected int $idServicioPagar;

    public function __construct(
        ?string $phoneNumber,
        string $mensajeTexto,
        ?string $instanciaWS,
        ?string $tokenWS,
        array $datosPDF,
        ?array $datosCorreo,
        int $idServicioPagar
    ) {
        $this->onQueue('alta');
        $this->phoneNumber = $phoneNumber;
        $this->mensajeTexto = $mensajeTexto;
        $this->instanciaWS = $instanciaWS;
        $this->tokenWS = $tokenWS;
        $this->datosPDF = $datosPDF;
        $this->datosCorreo = $datosCorreo;
        $this->idServicioPagar = $idServicioPagar;
    }

    public function handle(): void
    {

    // si la instanciaws es = null o no hay telefono no se envia el mensaje de texto por whatsapp y no se envia el pdf por whatsapp pero si se envia el correo
        if ($this->instanciaWS && $this->phoneNumber) {
            GenerarYEnviarComprobantePDFJob::dispatch(
                $this->phoneNumber,
                $this->datosPDF,
                $this->instanciaWS,
                $this->tokenWS,
                $this->mensajeTexto,
                $this->idServicioPagar
            );
        }else {
            \Log::info('No se envió mensaje de texto ni PDF por WhatsApp porque instanciaWS es null o el cliente no tiene teléfono', [
                'phoneNumber' => $this->phoneNumber,
                'mensajeTexto' => $this->mensajeTexto,
                'idServicioPagar' => $this->idServicioPagar,
            ]);
        }

        EnviarComprobantePagoEmailJob::dispatch(
            $this->idServicioPagar,
            $this->datosCorreo
        );
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('ProcesarPagoJob falló', [
            'idServicioPagar' => $this->idServicioPagar,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
