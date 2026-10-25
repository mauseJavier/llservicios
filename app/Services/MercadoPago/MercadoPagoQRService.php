<?php

namespace App\Services\MercadoPago;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            'access_token_configured' => ! empty($this->accessToken),
            'user_id_configured' => ! empty($this->userId),
            'sandbox_mode' => $this->sandbox,
        ]);
    }

    /**
     * Establecer el access token (por ejemplo, el de la empresa dueña de la caja).
     */
    public function setAccessToken(?string $accessToken): void
    {
        if (! empty($accessToken)) {
            $this->accessToken = $accessToken;
        }
    }

    /**
     * Establecer el user ID de Mercado Pago de la empresa.
     */
    public function setUserId(?string $userId): void
    {
        if (! empty($userId)) {
            $this->userId = $userId;
        }
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
                    'reference' => $storeData['location']['reference'] ?? '',
                ],
            ];

            // Agregar business_hours solo si está presente y no está vacío
            if (! empty($storeData['business_hours'])) {
                $data['business_hours'] = $storeData['business_hours'];
            }

            Log::info('MercadoPagoQR - Datos a enviar', [
                'data' => $data,
                'json' => json_encode($data),
                'url' => $this->baseUrl.'/users/'.$this->userId.'/stores',
                'token' => substr($this->accessToken, 0, 20).'...',
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/users/'.$this->userId.'/stores', $data);

            Log::info('MercadoPagoQR - Respuesta recibida', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('MercadoPagoQR - Sucursal creada exitosamente', [
                    'store_id' => $responseData['id'] ?? 'N/A',
                ]);

                return [
                    'success' => true,
                    'store_id' => $responseData['id'] ?? null,
                    'id' => $responseData['id'] ?? null,
                    'data' => $responseData,
                ];
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['message'] ?? $response->body();

            Log::error('MercadoPagoQR - Error en la API', [
                'status' => $response->status(),
                'error_body' => $errorBody,
                'full_response' => $response->body(),
            ]);

            return $this->errorResponse($response->status(), $errorMessage, $errorBody);

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error creando sucursal', [
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'causes' => [],
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
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/users/'.$this->userId.'/stores/'.$storeId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                throw new Exception('Error obteniendo sucursal: '.$response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo sucursal', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/users/'.$this->userId.'/stores');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                throw new Exception('Error listando sucursales: '.$response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error listando sucursales', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->put($this->baseUrl.'/users/'.$this->userId.'/stores/'.$storeId, $storeData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['message'] ?? $response->body();

            Log::error('MercadoPagoQR - Error actualizando sucursal', [
                'store_id' => $storeId,
                'status' => $response->status(),
                'error_body' => $errorBody,
            ]);

            return $this->errorResponse($response->status(), $errorMessage, $errorBody);

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error actualizando sucursal', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'causes' => [],
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
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->delete($this->baseUrl.'/users/'.$this->userId.'/stores/'.$storeId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Sucursal eliminada exitosamente',
                ];
            } else {
                throw new Exception('Error eliminando sucursal: '.$response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error eliminando sucursal', [
                'store_id' => $storeId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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
                'category' => $posData['category'] ?? null,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/pos', $data);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('MercadoPagoQR - Caja/POS creada exitosamente', [
                    'pos_id' => $responseData['id'],
                    'qr_code' => $responseData['qr']['image'] ?? 'No disponible',
                ]);

                return [
                    'success' => true,
                    'pos_id' => $responseData['id'],
                    'qr_code_image' => $responseData['qr']['image'] ?? null,
                    'qr_code_template' => $responseData['qr']['template_document'] ?? null,
                    'uuid' => $responseData['uuid'] ?? null,
                    'data' => $responseData,
                ];
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['message'] ?? $response->body();

            Log::error('MercadoPagoQR - Error en la API al crear caja/POS', [
                'status' => $response->status(),
                'error_body' => $errorBody,
            ]);

            return $this->errorResponse($response->status(), $errorMessage, $errorBody);

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error creando caja/POS', [
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'causes' => [],
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
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/pos/'.$posId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                throw new Exception('Error obteniendo caja: '.$response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo caja', [
                'pos_id' => $posId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->delete($this->baseUrl.'/pos/'.$posId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Caja eliminada exitosamente',
                ];
            } else {
                throw new Exception('Error eliminando caja: '.$response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error eliminando caja', [
                'pos_id' => $posId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ============================================================
    // GESTIÓN DE ÓRDENES QR
    // ============================================================

    /**
     * Crear orden QR (modelo integrado - monto fijo)
     *
     * @param  string  $posId  ID del POS/Caja
     * @param  array  $orderData  Datos de la orden
     */
    public function createQROrder(string $posId, array $orderData): array
    {
        try {
            Log::info('MercadoPagoQR - Creando orden QR', [
                'pos_id' => $posId,
                'order_data' => $orderData,
            ]);

            $data = [
                'external_reference' => $orderData['external_reference'] ?? 'order-'.time(),
                'title' => $orderData['title'],
                'description' => $orderData['description'] ?? '',
                'notification_url' => $orderData['notification_url'] ?? MercadoPagoApiService::getBaseUrl().'/api/mercadopago/webhook/qr',
                'total_amount' => floatval($orderData['total_amount']),
                'items' => $orderData['items'] ?? [],
            ];

            // Agregar tiempo de expiración si se proporciona
            if (isset($orderData['expiration_date'])) {
                $data['expiration_date'] = $orderData['expiration_date'];
            }

            // dd( 'Data to send:', $data , 'to URL: ' . $this->baseUrl . '/instore/orders/qr/seller/collectors/' . $this->userId . '/pos/' . $posId . '/qrs' , 'Using token: ' . ($this->accessToken) . '...');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/instore/orders/qr/seller/collectors/'.$this->userId.'/pos/'.$posId.'/qrs', $data);

            if ($response->successful()) {
                $responseData = $response->json();

                Log::info('MercadoPagoQR - Orden QR creada exitosamente', [
                    'qr_data' => $responseData,
                ]);

                return [
                    'success' => true,
                    'qr_data' => $responseData['qr_data'] ?? null,
                    'in_store_order_id' => $responseData['in_store_order_id'] ?? null,
                    'external_reference' => $data['external_reference'],
                    'data' => $responseData,
                ];
            } else {
                throw new Exception('Error en la API: '.$response->body(), $response->status());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error creando orden QR', [
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
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
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/v1/payments/'.$paymentId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                throw new Exception('Error obteniendo pago: '.$response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo pago', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Eliminar orden QR
     *
     * MercadoPago responde 400 "in_store_order_delete_error" o 404
     * "point_of_sale_in_store_order_not_found" cuando la caja no tiene una
     * orden pendiente que borrar (ya expiró, ya se pagó o nunca quedó
     * registrada). Ambos casos significan que el QR ya no está activo, por eso
     * se informan con "already_gone" en lugar de como error fatal.
     */
    public function deleteQROrder(string $posId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->delete($this->baseUrl.'/instore/orders/qr/seller/collectors/'.$this->userId.'/pos/'.$posId.'/qrs');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Orden QR eliminada exitosamente',
                    'status_code' => $response->status(),
                    'already_gone' => false,
                ];
            }

            $body = $response->json();
            $body = is_array($body) ? $body : [];
            $errorCode = $body['error'] ?? null;
            $alreadyGone = in_array($errorCode, [
                'in_store_order_delete_error',
                'point_of_sale_in_store_order_not_found',
            ], true) || $response->status() === 404;

            Log::warning('MercadoPagoQR - No se pudo eliminar la orden QR', [
                'pos_id' => $posId,
                'status_code' => $response->status(),
                'error_code' => $errorCode,
                'already_gone' => $alreadyGone,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'No se pudo cancelar la orden en Mercado Pago.',
                'error_code' => $errorCode,
                'status_code' => $response->status(),
                'already_gone' => $alreadyGone,
                'raw_response' => $body,
            ];

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error eliminando orden QR', [
                'pos_id' => $posId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'No se pudo contactar a Mercado Pago para cancelar la orden.',
                'error_code' => 'network_error',
                'status_code' => null,
                'already_gone' => false,
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
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/instore/qr/seller/collectors/'.$this->userId.'/pos/'.$posId.'/orders');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status_code' => $response->status(),
                    'not_found' => false,
                ];
            }

            $body = $response->json();
            $body = is_array($body) ? $body : [];
            $notFound = $response->status() === 404
                || ($body['error'] ?? null) === 'point_of_sale_in_store_order_not_found';

            Log::info('MercadoPagoQR - La caja no tiene una orden QR consultable', [
                'pos_id' => $posId,
                'status_code' => $response->status(),
                'error_code' => $body['error'] ?? null,
                'not_found' => $notFound,
            ]);

            return [
                'success' => false,
                'error' => 'No hay una orden QR activa en la caja.',
                'error_code' => $body['error'] ?? null,
                'status_code' => $response->status(),
                'not_found' => $notFound,
                'raw_response' => $body,
            ];

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo orden QR', [
                'pos_id' => $posId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'No se pudo consultar la orden en Mercado Pago.',
                'error_code' => 'network_error',
                'status_code' => null,
                'not_found' => false,
            ];
        }
    }

    // ============================================================
    // MÉTODOS AUXILIARES
    // ============================================================

    /**
     * Construir una respuesta de error uniforme a partir de una respuesta de la API.
     *
     * Incluye "causes", que MercadoPago usa para devolver el detalle de los
     * errores de validación (por ejemplo, la lista de ciudades válidas).
     */
    private function errorResponse(int $status, string $message, array $body = []): array
    {
        return [
            'success' => false,
            'error' => $message,
            'error_code' => $status,
            'causes' => is_array($body['causes'] ?? null) ? $body['causes'] : [],
            'raw_response' => $body,
        ];
    }

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
            'sandbox_mode' => $this->sandbox,
        ];
    }

    /**
     * Obtener el User ID desde la API
     */
    public function getUserId(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/users/me');

            if ($response->successful()) {
                $userData = $response->json();

                return [
                    'success' => true,
                    'user_id' => $userData['id'],
                    'email' => $userData['email'] ?? null,
                    'data' => $userData,
                ];
            } else {
                throw new Exception('Error obteniendo user ID: '.$response->body());
            }

        } catch (Exception $e) {
            Log::error('MercadoPagoQR - Error obteniendo user ID', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
