<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerarYEnviarComprobantePDFJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;
    public $timeout = 60;

    protected $phoneNumber;
    protected $datosPDF;
    protected $instanciaWS;
    protected $tokenWS;

    /**
     * Create a new job instance.
     */
    public function __construct($phoneNumber, array $datosPDF, $instanciaWS = null, $tokenWS = null)
    {
        $this->phoneNumber = $phoneNumber;
        $this->datosPDF = $datosPDF;
        $this->instanciaWS = $instanciaWS;
        $this->tokenWS = $tokenWS;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Generar el PDF de forma asíncrona
            $pdfBase64 = $this->generarComprobantePagoPDFBase64($this->datosPDF);

            // Preparar datos para envío por WhatsApp
            $datos = [
                'phoneNumber' => $this->phoneNumber,
                'message' => 'Comprobante de Pago adjunto.',
                'type' => 'document',
                'additionalData' => [
                    'filename' => 'comprobante_pago.pdf',
                    'caption' => 'Comprobante de Pago',
                    'base64' => $pdfBase64
                ],
                'instanciaWS' => $this->instanciaWS,
                'tokenWS' => $this->tokenWS
            ];

            // Despachar el job de WhatsApp
            EnviarWhatsAppJob::dispatch($datos);

        } catch (\Exception $e) {
            \Log::error('Error generando y enviando comprobante PDF', [
                'phoneNumber' => $this->phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Relanzar para que el job se reintente
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
