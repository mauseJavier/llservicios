<?php

namespace App\Http\Controllers\MercadoPago;

use App\Http\Controllers\Controller;
use App\Services\MercadoPago\MercadoPagoQRService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para gestión de códigos QR de Mercado Pago
 * Maneja sucursales, cajas y órdenes QR
 */
class MercadoPagoQRController extends Controller
{
    protected $qrService;

    public function __construct(MercadoPagoQRService $qrService)
    {
        $this->qrService = $qrService;
    }

    // ============================================================
    // GESTIÓN DE SUCURSALES
    // ============================================================

    /**
     * Crear una sucursal
     * POST /api/mercadopago/qr/stores
     */
    public function createStore(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'external_id' => 'required|string|max:60',
                'location' => 'required|array',
                'location.city_name' => 'required|string',
                'location.state_name' => 'required|string',
                'location.latitude' => 'required|numeric',
                'location.longitude' => 'required|numeric',
                'business_hours' => 'nullable|array'
            ]);

            $result = $this->qrService->createStore($request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sucursal creada exitosamente',
                    'data' => [
                        'store_id' => $result['store_id'],
                        'store' => $result['data']
                    ]
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la sucursal',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en createStore', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de una sucursal
     * GET /api/mercadopago/qr/stores/{storeId}
     */
    public function getStore(string $storeId): JsonResponse
    {
        try {
            $result = $this->qrService->getStore($storeId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener la sucursal',
                    'error' => $result['error']
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error en getStore', [
                'store_id' => $storeId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todas las sucursales
     * GET /api/mercadopago/qr/stores
     */
    public function listStores(): JsonResponse
    {
        try {
            $result = $this->qrService->listStores();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al listar sucursales',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en listStores', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una sucursal
     * PUT /api/mercadopago/qr/stores/{storeId}
     */
    public function updateStore(Request $request, string $storeId): JsonResponse
    {
        try {
            $result = $this->qrService->updateStore($storeId, $request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sucursal actualizada exitosamente',
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar la sucursal',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en updateStore', [
                'store_id' => $storeId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una sucursal
     * DELETE /api/mercadopago/qr/stores/{storeId}
     */
    public function deleteStore(string $storeId): JsonResponse
    {
        try {
            $result = $this->qrService->deleteStore($storeId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sucursal eliminada exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la sucursal',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en deleteStore', [
                'store_id' => $storeId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================================
    // GESTIÓN DE CAJAS/PDV
    // ============================================================

    /**
     * Crear una caja (POS)
     * POST /api/mercadopago/qr/pos
     */
    public function createPOS(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'store_id' => 'required|string',
                'external_store_id' => 'required|string',
                'external_id' => 'required|string|max:40',
                'fixed_amount' => 'nullable|boolean',
                'category' => 'nullable|integer'
            ]);

            $result = $this->qrService->createPOS($request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Caja creada exitosamente',
                    'data' => [
                        'pos_id' => $result['pos_id'],
                        'qr_code_image' => $result['qr_code_image'],
                        'qr_code_template' => $result['qr_code_template'],
                        'uuid' => $result['uuid'],
                        'pos' => $result['data']
                    ]
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la caja',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en createPOS', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de una caja
     * GET /api/mercadopago/qr/pos/{posId}
     */
    public function getPOS(string $posId): JsonResponse
    {
        try {
            $result = $this->qrService->getPOS($posId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener la caja',
                    'error' => $result['error']
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error en getPOS', [
                'pos_id' => $posId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una caja
     * DELETE /api/mercadopago/qr/pos/{posId}
     */
    public function deletePOS(string $posId): JsonResponse
    {
        try {
            $result = $this->qrService->deletePOS($posId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Caja eliminada exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la caja',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en deletePOS', [
                'pos_id' => $posId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================================
    // GESTIÓN DE ÓRDENES QR
    // ============================================================

    /**
     * Crear orden QR
     * POST /api/mercadopago/qr/pos/{posId}/orders
     */
    public function createQROrder(Request $request, string $posId): JsonResponse
    {
        try {
            $request->validate([
                'title' => 'required|string',
                'total_amount' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'external_reference' => 'nullable|string',
                'items' => 'nullable|array',
                'notification_url' => 'nullable|url'
            ]);

            $result = $this->qrService->createQROrder($posId, $request->all());

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Orden QR creada exitosamente',
                    'data' => [
                        'qr_data' => $result['qr_data'],
                        'in_store_order_id' => $result['in_store_order_id'],
                        'order' => $result['data']
                    ]
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la orden QR',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en createQROrder', [
                'pos_id' => $posId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener orden QR de un POS
     * GET /api/mercadopago/qr/pos/{posId}/orders
     */
    public function getQROrder(string $posId): JsonResponse
    {
        try {
            $result = $this->qrService->getQROrder($posId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener la orden QR',
                    'error' => $result['error']
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error en getQROrder', [
                'pos_id' => $posId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar orden QR
     * DELETE /api/mercadopago/qr/pos/{posId}/orders
     */
    public function deleteQROrder(string $posId): JsonResponse
    {
        try {
            $result = $this->qrService->deleteQROrder($posId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Orden QR eliminada exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar la orden QR',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en deleteQROrder', [
                'pos_id' => $posId,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================================
    // MÉTODOS AUXILIARES
    // ============================================================

    /**
     * Validar configuración QR
     * GET /api/mercadopago/qr/validate-config
     */
    public function validateConfig(): JsonResponse
    {
        try {
            $result = $this->qrService->validateQRConfig();

            return response()->json([
                'success' => $result['valid'],
                'data' => $result
            ], $result['valid'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Error en validateConfig', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error validando configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener User ID
     * GET /api/mercadopago/qr/user-id
     */
    public function getUserId(): JsonResponse
    {
        try {
            $result = $this->qrService->getUserId();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'user_id' => $result['user_id'],
                        'email' => $result['email']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error obteniendo user ID',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en getUserId', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
