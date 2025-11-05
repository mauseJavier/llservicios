<?php

namespace App\Services\MercadoPago;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Servicio para gestión de códigos QR de Mercado Pago
 * Maneja sucursales (stores), cajas (POS) y órdenes QR
 */
class MercadoPagoQRService
{
    private $accessToken;
    private $userId;
    private $baseUrl;
    private $sandbox;

    public function __construct()
    {
        $this->accessToken = config('services.mercadopago.access_token');
        $this->userId = config('services.mercadopago.user_id'); // Necesitarás agregar esto
        $this->baseUrl = 'https://api.mercadopago.com';
        $this->sandbox = config('services.mercadopago.sandbox', true);
        
        Log::info('MercadoPagoQRService inicializado', [
            'access_token_configured' => !empty($this->accessToken),
            'user_id_configured' => !empty($this->userId),
            'sandbox_mode' => $this->sandbox
        ]);
    }

    // ============================================================
    // GESTIÓN DE SUCURSALES (STORES)
    // ============================================================



    /**
     * Crear una sucursal
     */
    public function createStore(array $storeData): array
    {
        try {
            Log::info('MercadoPagoQR - Creando sucursal', ['store_data' => $storeData]);

            // Construir el objeto exactamente como lo requiere la API
            $data = [
                'name' => $storeData['name'],
                'external_id' => $storeData['external_id'],
                'location' => [
                    'street_number' => $storeData['location']['street_number'] ?? '',
                    'street_name' => $storeData['location']['street_name'] ?? '',
                    'city_name' => $storeData['location']['city_name'],
                    'state_name' => $storeData['location']['state_name'],
                    'latitude' => floatval($storeData['location']['latitude']),
                    'longitude' => floatval($storeData['location']['longitude']),
                    'reference' => $storeData['location']['reference'] ?? ''
                ]
            ];

            // Agregar business_hours solo si está presente y no está vacío
            if (!empty($storeData['business_hours'])) {
                $data['business_hours'] = $storeData['business_hours'];
            }

            Log::info('MercadoPagoQR - Datos a enviar', [
                'data' => $data,
                'json' => json_encode($data),
                'url' => $this->baseUrl . '/users/' . $this->userId . '/stores',
                'token' => substr($this->accessToken, 0, 20) . '...'
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/users/' . $this->userId . '/stores', $data);

            Log::info('MercadoPagoQR - Respuesta recibida', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('MercadoPagoQR - Sucursal creada exitosamente', [
                    'store_id' => $responseData['id'] ?? 'N/A'
                ]);

                return $responseData;
            } else {
                $errorBody = $response->json();
                $errorMessage = $errorBody['message'] ?? $response->body();
                
                Log::error('MercadoPagoQR - Error en la API', [
                    'status' => $response->status(),
                    'error_body' => $errorBody,
                    'full_response' => $response->body()
                ]);
                
                throw new Exception('Error en la API: ' . $errorMessage, $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error creando sucursal', [
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Obtener información de una sucursal
     */
    public function getStore(string $storeId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/users/' . $this->userId . '/stores/' . $storeId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                throw new Exception('Error obteniendo sucursal: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo sucursal', [
                'store_id' => $storeId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Listar todas las sucursales
     */
    public function listStores(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/users/' . $this->userId . '/stores');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                throw new Exception('Error listando sucursales: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error listando sucursales', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Actualizar una sucursal
     */
    public function updateStore(string $storeId, array $storeData): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->put($this->baseUrl . '/users/' . $this->userId . '/stores/' . $storeId, $storeData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                throw new Exception('Error actualizando sucursal: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error actualizando sucursal', [
                'store_id' => $storeId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar una sucursal
     */
    public function deleteStore(string $storeId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->delete($this->baseUrl . '/users/' . $this->userId . '/stores/' . $storeId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Sucursal eliminada exitosamente'
                ];
            } else {
                throw new Exception('Error eliminando sucursal: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error eliminando sucursal', [
                'store_id' => $storeId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ============================================================
    // GESTIÓN DE CAJAS/PDV (POINT OF SALE)
    // ============================================================

    /**
     * Crear una caja (POS)
     */
    public function createPOS(array $posData): array
    {
        try {
            Log::info('MercadoPagoQR - Creando caja/POS', ['pos_data' => $posData]);

            $data = [
                'name' => $posData['name'],
                'fixed_amount' => $posData['fixed_amount'] ?? true,
                'store_id' => $posData['store_id'],
                'external_store_id' => $posData['external_store_id'],
                'external_id' => $posData['external_id'],
                'category' => $posData['category'] ?? null
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/pos', $data);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('MercadoPagoQR - Caja/POS creada exitosamente', [
                    'pos_id' => $responseData['id'],
                    'qr_code' => $responseData['qr']['image'] ?? 'No disponible'
                ]);

                return [
                    'success' => true,
                    'pos_id' => $responseData['id'],
                    'qr_code_image' => $responseData['qr']['image'] ?? null,
                    'qr_code_template' => $responseData['qr']['template_document'] ?? null,
                    'uuid' => $responseData['uuid'] ?? null,
                    'data' => $responseData
                ];
            } else {
                throw new Exception('Error en la API: ' . $response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error creando caja/POS', [
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Obtener información de una caja
     */
    public function getPOS(string $posId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/pos/' . $posId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                throw new Exception('Error obteniendo caja: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo caja', [
                'pos_id' => $posId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar una caja
     */
    public function deletePOS(string $posId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->delete($this->baseUrl . '/pos/' . $posId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Caja eliminada exitosamente'
                ];
            } else {
                throw new Exception('Error eliminando caja: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error eliminando caja', [
                'pos_id' => $posId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ============================================================
    // GESTIÓN DE ÓRDENES QR
    // ============================================================

    /**
     * Crear orden QR (modelo integrado - monto fijo)
     * 
     * @param string $posId ID del POS/Caja
     * @param array $orderData Datos de la orden
     * @return array
     */
    public function createQROrder(string $posId, array $orderData): array
    {
        try {
            Log::info('MercadoPagoQR - Creando orden QR', [
                'pos_id' => $posId,
                'order_data' => $orderData
            ]);

            $data = [
                'external_reference' => $orderData['external_reference'] ?? 'order-' . time(),
                'title' => $orderData['title'],
                'description' => $orderData['description'] ?? '',
                'notification_url' => $orderData['notification_url'] ?? config('app.url') . '/api/mercadopago/webhook/qr',
                'total_amount' => floatval($orderData['total_amount']),
                'items' => $orderData['items'] ?? []
            ];

            // Agregar tiempo de expiración si se proporciona
            if (isset($orderData['expiration_date'])) {
                $data['expiration_date'] = $orderData['expiration_date'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/instore/qr/seller/collectors/' . $this->userId . '/pos/' . $posId . '/qrs', $data);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('MercadoPagoQR - Orden QR creada exitosamente', [
                    'qr_data' => $responseData
                ]);

                return [
                    'success' => true,
                    'qr_data' => $responseData['qr_data'] ?? null,
                    'in_store_order_id' => $responseData['in_store_order_id'] ?? null,
                    'external_reference' => $data['external_reference'],
                    'data' => $responseData
                ];
            } else {
                throw new Exception('Error en la API: ' . $response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error creando orden QR', [
                'error_message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Obtener estado de un pago
     */
    public function getPayment(string $paymentId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/v1/payments/' . $paymentId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                throw new Exception('Error obteniendo pago: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo pago', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Eliminar orden QR
     */
    public function deleteQROrder(string $posId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->delete($this->baseUrl . '/instore/qr/seller/collectors/' . $this->userId . '/pos/' . $posId . '/qrs');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Orden QR eliminada exitosamente'
                ];
            } else {
                throw new Exception('Error eliminando orden QR: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error eliminando orden QR', [
                'pos_id' => $posId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtener orden QR de un POS
     */
    public function getQROrder(string $posId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/instore/qr/seller/collectors/' . $this->userId . '/pos/' . $posId . '/qrs');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                throw new Exception('Error obteniendo orden QR: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo orden QR', [
                'pos_id' => $posId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ============================================================
    // MÉTODOS AUXILIARES
    // ============================================================

    /**
     * Validar configuración QR
     */
    public function validateQRConfig(): array
    {
        $errors = [];

        if (empty($this->accessToken)) {
            $errors[] = 'Access Token no configurado';
        }

        if (empty($this->userId)) {
            $errors[] = 'User ID no configurado';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sandbox_mode' => $this->sandbox
        ];
    }

    /**
     * Obtener el User ID desde la API
     */
    public function getUserId(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/users/me');

            if ($response->successful()) {
                $userData = $response->json();
                
                return [
                    'success' => true,
                    'user_id' => $userData['id'],
                    'email' => $userData['email'] ?? null,
                    'data' => $userData
                ];
            } else {
                throw new Exception('Error obteniendo user ID: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo user ID', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
