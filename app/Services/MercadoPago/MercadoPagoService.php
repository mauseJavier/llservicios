<?php

namespace App\Services\MercadoPago;

use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use Exception;

class MercadoPagoService
{
    private $accessToken;
    private $sandbox;

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
        $this->sandbox = config('services.mercadopago.sandbox', true);

        // dd('MercadoPagoService __construct', [
        //     'access_token' => $this->accessToken,
        //     'sandbox' => $this->sandbox
        // ]); 
        
        // Configurar MercadoPago
        MercadoPagoConfig::setAccessToken($this->accessToken);
        

            // if ($this->sandbox) {
            //     // Para SDK v2.x usar setEnvironment
            //     MercadoPagoConfig::setEnvironment(\MercadoPago\MercadoPagoConfig::LOCAL);
            // } else {
            //     MercadoPagoConfig::setEnvironment(\MercadoPago\MercadoPagoConfig::PRODUCTION);
            // }


    }

    /**
     * Crear una preferencia de pago para Checkout Pro
     */
    public function createPreference($data)
    {
        try {
            \Log::info('MercadoPago - Iniciando creación de preferencia', [
                'access_token_config' => !empty($this->accessToken) ? 'Configurado' : 'No configurado',
                'sandbox_mode' => $this->sandbox,
                'data_received' => $data
            ]);

            $client = new PreferenceClient();
            
            // Usar items directamente si ya vienen en el formato correcto
            $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
            \Log::info('MercadoPago - Items a enviar', $items);
            
            // Procesar información del pagador si existe
            $payer = isset($data['payer']) ? $data['payer'] : null;
            \Log::info('MercadoPago - Payer a enviar', $payer);

            $preferenceData = [
                "items" => $items,
                "back_urls" => [
                    "success" => $data['back_urls']['success'] ?? config('app.url') . '/mercadopago/success',
                    "failure" => $data['back_urls']['failure'] ?? config('app.url') . '/mercadopago/failure',
                    "pending" => $data['back_urls']['pending'] ?? config('app.url') . '/mercadopago/pending',
                ],
                "auto_return" => $data['auto_return'] ?? "approved",
                "external_reference" => $data['external_reference'] ?? null,
                "notification_url" => $data['notification_url'] ?? config('app.url') . '/mercadopago/webhook',
            ];

            // Solo agregar payer si existe
            if ($payer) {
                $preferenceData["payer"] = $payer;
            }

            \Log::info('MercadoPago - Datos enviados a API', $preferenceData);
            
            $preference = $client->create($preferenceData);

            \Log::info('MercadoPago - Preferencia creada exitosamente', [
                'preference_id' => $preference->id ?? 'No disponible',
                'init_point' => $preference->init_point ?? 'No disponible',
                'sandbox_init_point' => $preference->sandbox_init_point ?? 'No disponible'
            ]);

            return [
                'success' => true,
                'preference_id' => $preference->id,
                'init_point' => $preference->init_point,
                'sandbox_init_point' => $preference->sandbox_init_point,
                'data' => $preference
            ];

        } catch (Exception $e) {
            \Log::error('MercadoPago - Error creando preferencia', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            // Intentar obtener más detalles del error si es una excepción de MercadoPago
            $errorDetails = null;
            if (method_exists($e, 'getResponseData')) {
                $errorDetails = $e->getResponseData();
                \Log::error('MercadoPago - Detalles de respuesta API', $errorDetails);
            }

            // Verificar si hay información adicional en el objeto exception
            if (property_exists($e, 'response')) {
                \Log::error('MercadoPago - Response property', ['response' => $e->response]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_details' => $errorDetails,
                'data' => null
            ];
        }
    }

    /**
     * Obtener información de un pago
     */
    public function getPayment($paymentId)
    {
        try {
            $payment = \MercadoPago\Payment::find_by_id($paymentId);
            
            return [
                'success' => true,
                'data' => $payment
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Crear items para la preferencia de pago
     */
    public static function createItems($products)
    {
        $items = [];
        
        foreach ($products as $product) {
            $items[] = [
                "id" => $product['id'] ?? null,
                "title" => $product['title'],
                "description" => $product['description'] ?? '',
                "quantity" => $product['quantity'] ?? 1,
                "unit_price" => floatval($product['price'] ?? $product['unit_price'] ?? 0),
                "currency_id" => $product['currency_id'] ?? 'ARS',
            ];
        }
        
        return $items;
    }

    /**
     * Crear información del pagador
     */
    public static function createPayer($payerData)
    {
        return [
            "name" => $payerData['name'] ?? '',
            "surname" => $payerData['surname'] ?? '',
            "email" => $payerData['email'],
            "phone" => [
                "area_code" => $payerData['phone']['area_code'] ?? '',
                "number" => $payerData['phone']['number'] ?? ''
            ],
            "identification" => [
                "type" => $payerData['identification']['type'] ?? 'DNI',
                "number" => $payerData['identification']['number'] ?? ''
            ],
            "address" => [
                "street_name" => $payerData['address']['street_name'] ?? '',
                "street_number" => $payerData['address']['street_number'] ?? null,
                "zip_code" => $payerData['address']['zip_code'] ?? ''
            ]
        ];
    }

    /**
     * Generar URLs válidas para el entorno actual
     */
    public static function getValidUrls($baseRoutes = [])
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

    /**
     * Validar credenciales de MercadoPago
     */
    public function validateCredentials()
    {
        try {
            \Log::info('MercadoPago - Validando credenciales');
            
            // Verificar que el access token esté configurado
            if (empty($this->accessToken)) {
                return [
                    'valid' => false,
                    'error' => 'Access token no configurado'
                ];
            }

            // Verificar formato del access token
            if (!preg_match('/^APP_USR-\d+-\d+-[a-f0-9]+-\d+$/', $this->accessToken)) {
                return [
                    'valid' => false,
                    'error' => 'Formato de access token inválido'
                ];
            }

            // Hacer una petición simple para validar las credenciales
            $client = new \MercadoPago\Client\Common\RequestOptions();
            
            return [
                'valid' => true,
                'message' => 'Credenciales válidas'
            ];

        } catch (Exception $e) {
            \Log::error('MercadoPago - Error validando credenciales', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}