<?php

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $message;
    protected $type;
    protected $additionalData;
    protected $instanciaWS;
    protected $tokenWS;

    /**
     * Número de intentos antes de fallar
     */
    public $tries = 3;

    /**
     * Tiempo de espera en segundos antes de reintentar
     */
    public $backoff = 10;

    /**
     * Create a new job instance.
     *
     * @param string $phoneNumber Número de teléfono
     * @param string $message Mensaje o URL (depende del tipo)
     * @param string $type Tipo: 'text', 'document', 'image'
     * @param array $additionalData Datos adicionales (caption, filename, etc)
     */
    public function __construct(array $datos)
    {
        $this->phoneNumber = $datos['phoneNumber'] ?? null;
        $this->message = $datos['message'] ?? null;
        $this->type = $datos['type'] ?? 'text';
        $this->additionalData = $datos['additionalData'] ?? [];
        $this->instanciaWS = $datos['instanciaWS'] ?? env('WHATSAPP_INSTANCE_ID', null);
        $this->tokenWS = $datos['tokenWS'] ?? env('WHATSAPP_API_KEY', null);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $whatsappService = app()->make(WhatsAppService::class, [

            'instanciaWS' => $this->instanciaWS ?? null,
            'tokenWS' => $this->tokenWS ?? null,
        ]);
    
        Log::info('WhatsApp Job - Iniciando envío', [
            'phone' => $this->phoneNumber,
            'type' => $this->type,
            'attempt' => $this->attempts()
        ]);

        try {
            $result = match($this->type) {
                'text' => $whatsappService->sendTextMessage(
                    $this->phoneNumber,
                    $this->message,
                    $this->additionalData
                ),
                'document' => $whatsappService->sendDocument(
                    $this->phoneNumber,
                    $this->message, // URL del documento
                    $this->additionalData['filename'] ?? 'documento.pdf',
                    $this->additionalData['caption'] ?? null,
                    $this->additionalData,
                    $this->additionalData['base64'] ?? null
                ),
                'image' => $whatsappService->sendImage(
                    $this->phoneNumber,
                    $this->message, // URL de la imagen
                    $this->additionalData['caption'] ?? null,
                    $this->additionalData
                ),
                'custom' => $whatsappService->sendCustomMessage(
                    array_merge($this->additionalData, [
                        'key' => [
                            'remoteJid' => $this->phoneNumber . '@s.whatsapp.net',
                            'fromMe' => true,
                        ]
                    ])
                ),
                default => throw new \Exception("Tipo de mensaje no soportado: {$this->type}")
            };


            Log::info('WhatsApp Job - Mensaje enviado exitosamente', [
                'phone' => $this->phoneNumber,
                'type' => $this->type
            ]);

        } catch (\Exception $e) {
            Log::error('WhatsApp Job - Error en el envío', [
                'phone' => $this->phoneNumber,
                'type' => $this->type,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Si aún quedan intentos, lanzar la excepción para que se reintente
            if ($this->attempts() < $this->tries) {
                throw $e;
            }

            // Si ya se agotaron los intentos, registrar el fallo definitivo
            Log::error('WhatsApp Job - Fallo definitivo después de todos los intentos', [
                'phone' => $this->phoneNumber,
                'type' => $this->type,
                'total_attempts' => $this->attempts()
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('WhatsApp Job - Job fallido completamente', [
            'phone' => $this->phoneNumber,
            'type' => $this->type,
            'error' => $exception->getMessage()
        ]);

        // Aquí podrías notificar al administrador o tomar alguna acción
    }
}
