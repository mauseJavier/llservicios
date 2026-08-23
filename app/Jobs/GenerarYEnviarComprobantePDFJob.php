<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\WhatsAppService;

class GenerarYEnviarComprobantePDFJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;
    public $timeout = 120;

    protected $phoneNumber;
    protected $datosPDF;
    protected $instanciaWS;
    protected $tokenWS;
    protected $mensajeTexto;
    protected ?int $idServicioPagar;

    public function __construct($phoneNumber, array $datosPDF, $instanciaWS = null, $tokenWS = null, $mensajeTexto = null, ?int $idServicioPagar = null)
    {
        $this->onQueue('notificaciones');
        $this->phoneNumber = $phoneNumber;
        $this->datosPDF = $datosPDF;
        $this->instanciaWS = $instanciaWS;
        $this->tokenWS = $tokenWS;
        $this->mensajeTexto = $mensajeTexto;
        $this->idServicioPagar = $idServicioPagar;
    }

    public function handle(): void
    {
        try {
            if (empty($this->phoneNumber)) {
                \Log::info('GenerarYEnviarComprobantePDFJob omitido porque el cliente no tiene teléfono', [
                    'datosPDF' => $this->datosPDF,
                ]);
                return;
            }

            // Idempotencia: verificar que no se haya enviado ya este comprobante para este servicio y teléfono
            $cacheKey = 'whatsapp_comprobante_pago_' . $this->idServicioPagar . '_' . $this->phoneNumber;
            if (!Cache::add($cacheKey, 1, 86400)) {
                \Log::info('GenerarYEnviarComprobantePDFJob omitido por idempotencia (ya enviado previamente)', [
                    'phoneNumber' => $this->phoneNumber,
                    'idServicioPagar' => $this->idServicioPagar,
                    'cacheKey' => $cacheKey,
                ]);
                return;
            }

            $whatsappService = app()->make(WhatsAppService::class, [
                'instanciaWS' => $this->instanciaWS,
                'tokenWS' => $this->tokenWS,
            ]);

            if ($this->mensajeTexto) {
                $whatsappService->sendButtons(
                    $this->phoneNumber,
                    '🧾 Comprobante de Pago',
                    $this->mensajeTexto,
                    'Gracias por su pago',
                    [
                        ['type' => 'reply', 'displayText' => 'Información recibida', 'id' => 'info_recibida'],
                    ]
                );
            }

            $pdfBase64 = $this->generarComprobantePagoPDFBase64($this->datosPDF);

            $whatsappService->sendDocument(
                $this->phoneNumber,
                'Comprobante de Pago adjunto.',
                'comprobante_pago.pdf',
                'Comprobante de Pago',
                [],
                $pdfBase64
            );

        } catch (\Exception $e) {
            \Log::error('Error generando y enviando comprobante PDF', [
                'phoneNumber' => $this->phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Genera un PDF pequeño con los datos del pago y retorna el archivo en base64
     *
     * @param array $datosPago Datos requeridos para el comprobante
     * @return string base64
     */
    private function generarComprobantePagoPDFBase64(array $datosPago)
    {
        // Plantilla simple en HTML para el PDF
        $html = view('pdf.comprobante_pago', $datosPago)->render();

        // Generar el PDF usando DomPDF
        $pdf = Pdf::loadHTML($html)->setPaper('a6'); // a6: pequeño

        // Obtener el contenido binario del PDF
        $output = $pdf->output();

        // Codificar en base64
        $base64 = base64_encode($output);

        return $base64;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Job GenerarYEnviarComprobantePDFJob falló después de todos los reintentos', [
            'phoneNumber' => $this->phoneNumber,
            'error' => $exception->getMessage()
        ]);
    }
}
