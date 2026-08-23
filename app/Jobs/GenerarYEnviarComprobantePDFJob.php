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
        $phoneNormalized = preg_replace('/[^0-9]/', '', (string) $this->phoneNumber);
        $baseKey = 'whatsapp_comprobante_pago_' . $this->idServicioPagar . '_' . $phoneNormalized;
        $lockKey = $baseKey . '_lock';
        $deliveredKey = $baseKey . '_delivered';

        try {
            if (empty($this->phoneNumber)) {
                \Log::info('GenerarYEnviarComprobantePDFJob omitido porque el cliente no tiene teléfono', [
                    'datosPDF' => $this->datosPDF,
                ]);
                return;
            }

            if (Cache::has($deliveredKey)) {
                \Log::info('GenerarYEnviarComprobantePDFJob omitido por idempotencia (ya enviado previamente)', [
                    'phoneNumber' => $this->phoneNumber,
                    'idServicioPagar' => $this->idServicioPagar,
                    'cacheKey' => $deliveredKey,
                ]);
                return;
            }

            if (!Cache::add($lockKey, 1, 120)) {
                \Log::info('GenerarYEnviarComprobantePDFJob omitido por lock activo (concurrencia)', [
                    'phoneNumber' => $this->phoneNumber,
                    'idServicioPagar' => $this->idServicioPagar,
                    'cacheKey' => $lockKey,
                ]);
                return;
            }

            $whatsappService = app()->make(WhatsAppService::class, [
                'instanciaWS' => $this->instanciaWS,
                'tokenWS' => $this->tokenWS,
            ]);

            $buttonResult = ['success' => true, 'message' => 'not_sent'];
            if ($this->mensajeTexto) {
                $buttonResult = $whatsappService->sendButtons(
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

            $pdfResult = $whatsappService->sendDocument(
                $this->phoneNumber,
                'Comprobante de Pago adjunto.',
                'comprobante_pago.pdf',
                'Comprobante de Pago',
                [],
                $pdfBase64
            );

            $buttonSuccess = (bool) ($buttonResult['success'] ?? false);
            $pdfSuccess = (bool) ($pdfResult['success'] ?? false);

            \Log::info('Resultado envío WhatsApp comprobante', [
                'idServicioPagar' => $this->idServicioPagar,
                'phoneNumber' => $this->phoneNumber,
                'button_success' => $buttonSuccess,
                'pdf_success' => $pdfSuccess,
            ]);

            if (!$buttonSuccess || !$pdfSuccess) {
                throw new \RuntimeException('Fallo envío WhatsApp comprobante (botón o PDF).');
            }

            Cache::put($deliveredKey, 1, 60 * 60 * 24 * 7);
            Cache::forget($lockKey);

        } catch (\Exception $e) {
            Cache::forget($lockKey);
            \Log::error('Error generando y enviando comprobante PDF', [
                'idServicioPagar' => $this->idServicioPagar,
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
