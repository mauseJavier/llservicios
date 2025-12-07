<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WhatsAppService
{
    private $apiUrl;
    private $apiKey;
    private $instanceId;

    public function __construct($instanciaWS = null, $tokenWS = null)
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiKey = config('services.whatsapp.api_key');
        $this->instanceId = config('services.whatsapp.instance_id');

        if ($instanciaWS) {
            $this->instanceId = $instanciaWS;
        }

        if ($tokenWS) {
            $this->apiKey = $tokenWS;
        }
    }

        /**
     * Enviar mensaje de texto por WhatsApp
     * 
     * @param string $phoneNumber Número de teléfono con código de país (ej: 5492942506803)
     * @param string $message Texto del mensaje a enviar
     * @param array $options Opciones adicionales (delay, linkPreview, etc)
     * @return array
     */
    public function sendTextMessage(string $phoneNumber, string $message, array $options = []): array
    {
        try {
            Log::info('WhatsApp - Enviando mensaje de texto', [
                'phone' => $phoneNumber,
                'message_preview' => substr($message, 0, 50)
            ]);

            // Limpiar el número de teléfono (solo números)
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            // Asegurar que tenga el prefijo 549 para Argentina
            if (!str_starts_with($phoneNumber, '549')) {
                $phoneNumber = '549' . $phoneNumber;
            }

            // Preparar el payload según la API de Evolution
            $payload = [
                'number' => $phoneNumber,
                'text' => $message,
                'delay' => $options['delay'] ?? 0,
                'linkPreview' => $options['linkPreview'] ?? true,
            ];

            // Agregar menciones si existen
            if (isset($options['mentionsEveryOne'])) {
                $payload['mentionsEveryOne'] = $options['mentionsEveryOne'];
            }
            
            if (isset($options['mentioned'])) {
                $payload['mentioned'] = $options['mentioned'];
            }

            // Realizar la petición HTTP
            $response = $this->makeRequest('POST', '/message/sendText/' . $this->instanceId, $payload);

            Log::info('WhatsApp - Mensaje enviado exitosamente', [
                'phone' => $phoneNumber,
                'response' => $response
            ]);

            return [
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
                'data' => $response
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp - Error enviando mensaje de texto', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar mensaje: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

        /**
     * Enviar documento por WhatsApp
     * 
     * @param string $phoneNumber Número de teléfono con código de país
     * @param string $documentUrl URL del documento a enviar
     * @param string $filename Nombre del archivo
     * @param string|null $caption Texto opcional para acompañar el documento
     * @param array $options Opciones adicionales
     * @return array
     */
    public function sendDocument(string $phoneNumber, string $documentUrl, string $filename, ?string $caption = null, array $options = [], ?string $base64 = null): array
    {
        try {
            Log::info('WhatsApp - Enviando documento', [
                'phone' => $phoneNumber,
                'filename' => $filename,
                'document_url' => $documentUrl
            ]);

            // Limpiar el número de teléfono
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
            
            if (!str_starts_with($phoneNumber, '549')) {
                $phoneNumber = '549' . $phoneNumber;
            }

            // Preparar el payload según la API de Evolution
            $payload = [
                'number' => $phoneNumber,
                'mediatype' => 'document',
                'mimetype' => 'application/pdf',
                'media' => $base64 ?? $documentUrl,
                'fileName' => $filename,
                'delay' => $options['delay'] ?? 0,
            ];

            if ($caption) {
                $payload['caption'] = $caption;
            }

            // Realizar la petición HTTP
            $response = $this->makeRequest('POST', '/message/sendMedia/' . $this->instanceId, $payload);

            Log::info('WhatsApp - Documento enviado exitosamente', [
                'phone' => $phoneNumber,
                'filename' => $filename,
                'response' => $response
            ]);

            return [
                'success' => true,
                'message' => 'Documento enviado correctamente',
                'data' => $response
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp - Error enviando documento', [
                'phone' => $phoneNumber,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar documento: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Enviar imagen por WhatsApp
     * 
     * @param string $phoneNumber Número de teléfono con código de país
     * @param string $imageUrl URL de la imagen a enviar
     * @param string|null $caption Texto opcional para acompañar la imagen
     * @param array $options Opciones adicionales
     * @return array
     */
    public function sendImage(string $phoneNumber, string $imageUrl, ?string $caption = null, array $options = []): array
    {
        try {
            Log::info('WhatsApp - Enviando imagen', [
                'phone' => $phoneNumber,
                'image_url' => $imageUrl
            ]);

            $remoteJid = $this->formatPhoneNumber($phoneNumber);

            $payload = [
                'key' => [
                    'remoteJid' => $remoteJid,
                    'fromMe' => true,
                    'id' => $this->generateMessageId()
                ],
                'pushName' => $options['pushName'] ?? config('app.name'),
                'status' => 'PENDING',
                'message' => [
                    'imageMessage' => [
                        'url' => $imageUrl,
                        'caption' => $caption
                    ]
                ],
                'messageType' => 'imageMessage',
                'messageTimestamp' => time(),
                'instanceId' => $options['instanceId'] ?? $this->instanceId,
                'source' => $options['source'] ?? 'api'
            ];

            $response = $this->makeRequest('POST', '/sendImage', $payload);

            Log::info('WhatsApp - Imagen enviada exitosamente', [
                'phone' => $phoneNumber,
                'response' => $response
            ]);

            return [
                'success' => true,
                'data' => $response,
                'message' => 'Imagen enviada correctamente'
            ];

        } catch (Exception $e) {
            Log::error('WhatsApp - Error enviando imagen', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Enviar mensaje personalizado con estructura completa
     * 
     * @param array $payload Estructura completa del mensaje
     * @return array
     */
    // public function sendCustomMessage(array $payload): array
    // {
    //     try {
    //         Log::info('WhatsApp - Enviando mensaje personalizado', [
    //             'payload_type' => $payload['messageType'] ?? 'unknown'
    //         ]);

    //         // Asegurar que tenga los campos requeridos
    //         if (!isset($payload['key']['remoteJid'])) {
    //             throw new Exception('El campo remoteJid es requerido');
    //         }

    //         if (!isset($payload['message'])) {
    //             throw new Exception('El campo message es requerido');
    //         }

    //         // Agregar campos por defecto si no existen
    //         $payload['messageTimestamp'] = $payload['messageTimestamp'] ?? time();
    //         $payload['instanceId'] = $payload['instanceId'] ?? $this->instanceId;
    //         $payload['status'] = $payload['status'] ?? 'PENDING';

    //         $response = $this->makeRequest('POST', '/sendMessage', $payload);

    //         Log::info('WhatsApp - Mensaje personalizado enviado exitosamente', [
    //             'response' => $response
    //         ]);

    //         return [
    //             'success' => true,
    //             'data' => $response,
    //             'message' => 'Mensaje enviado correctamente'
    //         ];

    //     } catch (Exception $e) {
    //         Log::error('WhatsApp - Error enviando mensaje personalizado', [
    //             'error' => $e->getMessage()
    //         ]);

    //         return [
    //             'success' => false,
    //             'error' => $e->getMessage(),
    //             'data' => null
    //         ];
    //     }
    // }

        /**
     * Realizar petición HTTP a la API de WhatsApp
     * 
     * @param string $method Método HTTP (GET, POST, etc)
     * @param string $endpoint Endpoint de la API
     * @param array $data Datos a enviar
     * @return array
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        // Validar que la URL base esté configurada
        if (empty($this->apiUrl)) {
            throw new Exception('WhatsApp API URL no está configurada. Verifica tu archivo .env (WHATSAPP_API_URL)');
        }

        if (empty($this->apiKey)) {
            throw new Exception('WhatsApp API Key no está configurada. Verifica tu archivo .env (WHATSAPP_API_KEY)');
        }

        if (empty($this->instanceId)) {
            throw new Exception('WhatsApp Instance ID no está configurada. Verifica tu archivo .env (WHATSAPP_INSTANCE_ID)');
        }

        // $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');
        $url = $this->apiUrl . '' . $endpoint;


        Log::info("WhatsApp - Realizando petición {$method} a {$url}", [
            'endpoint' => $endpoint,
            'api_url' => $this->apiUrl,
            'full_url' => $url,
            'data' => $data
        ]);

        // Construir la petición con headers correctos según la API de Evolution
        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $this->apiKey,
            ]);

        $response = match(strtoupper($method)) {
            'GET' => $response->get($url, $data),
            'POST' => $response->post($url, $data),
            'PUT' => $response->put($url, $data),
            'DELETE' => $response->delete($url, $data),
            default => throw new Exception("Método HTTP no soportado: {$method}")
        };

        if (!$response->successful()) {
            $errorMessage = $response->json()['message'] ?? $response->body();
            throw new Exception("Error en petición WhatsApp ({$response->status()}): {$errorMessage}");
        }

        return $response->json() ?? ['response' => $response->body()];
    }



    /**
     * Generar ID único para el mensaje
     * 
     * @return string
     */
    private function generateMessageId(): string
    {
        return strtoupper(bin2hex(random_bytes(20)));
    }

    /**
     * Validar configuración del servicio
     * 
     * @return array
     */
    public function validateConfiguration(): array
    {
        $errors = [];

        if (empty($this->apiUrl)) {
            $errors[] = 'API URL no configurada';
        }

        if (empty($this->instanceId)) {
            $errors[] = 'Instance ID no configurado';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'config' => [
                'api_url' => $this->apiUrl ? '***' : null,
                'instance_id' => $this->instanceId ? '***' : null,
                'api_key_set' => !empty($this->apiKey)
            ]
        ];
    }
}
