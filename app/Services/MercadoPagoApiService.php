<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MercadoPagoApiService
{
    private $accessToken;
    private $sandbox;
    private $baseUrl;

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
        $this->sandbox = config('services.mercadopago.sandbox', true);
        $this->baseUrl = 'https://api.mercadopago.com';
        
        Log::info('MercadoPagoApiService inicializado', [
            'access_token_configured' => !empty($this->accessToken),
            'sandbox_mode' => $this->sandbox
        ]);
    }

    /**
     * Crear una preferencia de pago usando la API REST
     */
    public function createPreference(array $data): array
    {
        try {
            Log::info('MercadoPagoAPI - Iniciando creación de preferencia', [
                'data_received' => $data
            ]);

            $preferenceData = $this->buildPreferenceData($data);
            
            Log::info('MercadoPagoAPI - Datos a enviar', $preferenceData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
                'X-Idempotency-Key' => $this->generateIdempotencyKey()
            ])->post($this->baseUrl . '/checkout/preferences', $preferenceData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('MercadoPagoAPI - Preferencia creada exitosamente', [
                    'preference_id' => $responseData['id'] ?? 'No disponible',
                    'init_point' => $responseData['init_point'] ?? 'No disponible'
                ]);

                return [
                    'success' => true,
                    'preference_id' => $responseData['id'],
                    'init_point' => $responseData['init_point'],
                    'sandbox_init_point' => $responseData['sandbox_init_point'] ?? null,
                    'data' => $responseData
                ];
            } else {
                throw new Exception('Error en la API: ' . $response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoAPI - Error creando preferencia', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'data' => null
            ];
        }
    }

    /**
     * Obtener información de un pago
     */
    public function getPayment(string $paymentId): array
    {
        try {
            Log::info('MercadoPagoAPI - Obteniendo información de pago', [
                'payment_id' => $paymentId
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/v1/payments/' . $paymentId);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('MercadoPagoAPI - Pago obtenido exitosamente', [
                    'payment_id' => $responseData['id'] ?? 'No disponible',
                    'status' => $responseData['status'] ?? 'No disponible'
                ]);

                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                throw new Exception('Error obteniendo pago: ' . $response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoAPI - Error obteniendo pago', [
                'payment_id' => $paymentId,
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Obtener información de una preferencia
     */
    public function getPreference(string $preferenceId): array
    {
        try {
            Log::info('MercadoPagoAPI - Obteniendo preferencia', [
                'preference_id' => $preferenceId
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/checkout/preferences/' . $preferenceId);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('MercadoPagoAPI - Preferencia obtenida exitosamente', [
                    'preference_id' => $responseData['id'] ?? 'No disponible'
                ]);

                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                throw new Exception('Error obteniendo preferencia: ' . $response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoAPI - Error obteniendo preferencia', [
                'preference_id' => $preferenceId,
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Crear un pago directo (Payment)
     */
    public function createPayment(array $paymentData): array
    {
        try {
            Log::info('MercadoPagoAPI - Creando pago directo', [
                'payment_data' => $paymentData
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
                'X-Idempotency-Key' => $this->generateIdempotencyKey()
            ])->post($this->baseUrl . '/v1/payments', $paymentData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('MercadoPagoAPI - Pago creado exitosamente', [
                    'payment_id' => $responseData['id'] ?? 'No disponible',
                    'status' => $responseData['status'] ?? 'No disponible'
                ]);

                return [
                    'success' => true,
                    'payment_id' => $responseData['id'],
                    'status' => $responseData['status'],
                    'data' => $responseData
                ];
            } else {
                throw new Exception('Error creando pago: ' . $response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoAPI - Error creando pago', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'data' => null
            ];
        }
    }

    /**
     * Obtener métodos de pago disponibles
     */
    public function getPaymentMethods(): array
    {
        try {
            Log::info('MercadoPagoAPI - Obteniendo métodos de pago');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/v1/payment_methods');

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('MercadoPagoAPI - Métodos de pago obtenidos exitosamente', [
                    'total_methods' => count($responseData)
                ]);

                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                throw new Exception('Error obteniendo métodos de pago: ' . $response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoAPI - Error obteniendo métodos de pago', [
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Validar credenciales haciendo una petición de prueba
     */
    public function validateCredentials(): array
    {
        try {
            Log::info('MercadoPagoAPI - Validando credenciales');
            
            if (empty($this->accessToken)) {
                return [
                    'valid' => false,
                    'error' => 'Access token no configurado'
                ];
            }

            // Verificar formato del access token
            if (!preg_match('/^(APP_USR|TEST)-\w+/', $this->accessToken)) {
                return [
                    'valid' => false,
                    'error' => 'Formato de access token inválido'
                ];
            }

            // Hacer una petición simple para validar las credenciales
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/v1/payment_methods');

            if ($response->successful()) {
                Log::info('MercadoPagoAPI - Credenciales válidas');
                return [
                    'valid' => true,
                    'message' => 'Credenciales válidas'
                ];
            } else {
                throw new Exception('Credenciales inválidas: ' . $response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoAPI - Error validando credenciales', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Construir datos de preferencia
     */
    private function buildPreferenceData(array $data): array
    {
        $preferenceData = [
            'items' => $data['items'] ?? [],
            'back_urls' => [
                'success' => $data['back_urls']['success'] ?? config('app.url') . '/mercadopago/success',
                'failure' => $data['back_urls']['failure'] ?? config('app.url') . '/mercadopago/failure',
                'pending' => $data['back_urls']['pending'] ?? config('app.url') . '/mercadopago/pending',
            ],
            'notification_url' => $data['notification_url'] ?? config('app.url') . '/mercadopago/webhook',
        ];

        // auto_return debe ser específicamente "approved" o "all", o no enviarse
        if (isset($data['auto_return']) && in_array($data['auto_return'], ['approved', 'all'])) {
            $preferenceData['auto_return'] = $data['auto_return'];
        }

        // Agregar external_reference solo si está presente y no está vacío
        if (!empty($data['external_reference'])) {
            $preferenceData['external_reference'] = $data['external_reference'];
        }

        // Agregar payer solo si está presente
        if (isset($data['payer']) && is_array($data['payer'])) {
            $preferenceData['payer'] = $data['payer'];
        }

        // Agregar payment_methods solo si está presente
        if (isset($data['payment_methods']) && is_array($data['payment_methods'])) {
            $preferenceData['payment_methods'] = $data['payment_methods'];
        }

        // Agregar expires solo si está presente y tiene los campos requeridos
        if (isset($data['expires']) && is_array($data['expires'])) {
            if (!empty($data['expires']['date_from']) && !empty($data['expires']['date_to'])) {
                $preferenceData['expires'] = $data['expires'];
            }
        }

        // Agregar shipments solo si está presente
        if (isset($data['shipments']) && is_array($data['shipments'])) {
            $preferenceData['shipments'] = $data['shipments'];
        }

        return $preferenceData;
    }

    /**
     * Generar clave de idempotencia
     */
    private function generateIdempotencyKey(): string
    {
        return uniqid('mp_api_', true);
    }

    /**
     * Crear items para la preferencia de pago
     */
    public static function createItems(array $products): array
    {
        $items = [];
        
        foreach ($products as $product) {
            $item = [
                'title' => $product['title'],
                'quantity' => (int) ($product['quantity'] ?? 1),
                'unit_price' => (float) ($product['price'] ?? $product['unit_price'] ?? 0),
                'currency_id' => $product['currency_id'] ?? 'ARS',
            ];

            // Agregar ID solo si está presente y no está vacío
            if (!empty($product['id'])) {
                $item['id'] = $product['id'];
            }

            // Agregar descripción solo si está presente y no está vacía
            if (!empty($product['description'])) {
                $item['description'] = $product['description'];
            }

            $items[] = $item;
        }
        
        return $items;
    }

    /**
     * Crear información del pagador
     */
    public static function createPayer(array $payerData): array
    {
        $payer = [
            'email' => $payerData['email'],
        ];

        // Agregar nombre solo si está presente y no está vacío
        if (!empty($payerData['name'])) {
            $payer['name'] = $payerData['name'];
        }

        // Agregar apellido solo si está presente y no está vacío
        if (!empty($payerData['surname'])) {
            $payer['surname'] = $payerData['surname'];
        }

        // Agregar teléfono solo si tiene al menos el número
        if (!empty($payerData['phone']['number'])) {
            $payer['phone'] = [
                'number' => $payerData['phone']['number']
            ];
            
            // Agregar código de área solo si está presente
            if (!empty($payerData['phone']['area_code'])) {
                $payer['phone']['area_code'] = $payerData['phone']['area_code'];
            }
        }

        // Agregar identificación solo si está presente
        if (!empty($payerData['identification']['number'])) {
            $payer['identification'] = [
                'type' => $payerData['identification']['type'] ?? 'DNI',
                'number' => (string) $payerData['identification']['number']
            ];
        }

        // Agregar dirección solo si tiene al menos el nombre de la calle
        if (!empty($payerData['address']['street_name'])) {
            $payer['address'] = [
                'street_name' => $payerData['address']['street_name']
            ];
            
            // Agregar número de calle solo si está presente y no es null
            if (!empty($payerData['address']['street_number'])) {
                $payer['address']['street_number'] = (string) $payerData['address']['street_number'];
            }
            
            // Agregar código postal solo si está presente
            if (!empty($payerData['address']['zip_code'])) {
                $payer['address']['zip_code'] = $payerData['address']['zip_code'];
            }
        }

        return $payer;
    }

    /**
     * Generar URLs válidas para el entorno actual
     */
    public static function getValidUrls(array $baseRoutes = []): array
    {
        $appUrl = config('app.url');
        $isLocalhost = $appUrl === 'http://localhost' || strpos($appUrl, '://localhost') !== false;
        
        if ($isLocalhost && config('app.env') === 'local') {
            // En desarrollo local, usar URLs de prueba
            return [
                'success' => 'https://httpbin.org/get?success=true&app=' . urlencode(config('app.name')),
                'failure' => 'https://httpbin.org/get?failure=true&app=' . urlencode(config('app.name')),
                'pending' => 'https://httpbin.org/get?pending=true&app=' . urlencode(config('app.name')),
                'webhook' => 'https://httpbin.org/post'
            ];
        }
        
        // En producción, usar URLs reales
        return [
            'success' => $appUrl . '/mercadopago/success',
            'failure' => $appUrl . '/mercadopago/failure', 
            'pending' => $appUrl . '/mercadopago/pending',
            'webhook' => $appUrl . '/mercadopago/webhook'
        ];
    }
}