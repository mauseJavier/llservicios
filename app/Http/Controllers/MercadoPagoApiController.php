<?php

namespace App\Http\Controllers;

use App\Services\MercadoPagoApiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MercadoPagoApiController extends Controller
{
    protected $mercadoPagoApiService;

    public function __construct(MercadoPagoApiService $mercadoPagoApiService)
    {
        $this->mercadoPagoApiService = $mercadoPagoApiService;
    }

    /**
     * Crear una preferencia de pago usando la API directa
     */
    public function createPreference(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'servicios' => 'required|array',
                'servicios.*.id' => 'required',
                'servicios.*.titulo' => 'required|string',
                'servicios.*.precio' => 'required|numeric|min:0',
                'cliente' => 'required|array',
                'cliente.email' => 'required|email',
                'referencia_externa' => 'nullable|string'
            ]);

            // Preparar items usando el helper del servicio
            $items = MercadoPagoApiService::createItems(
                collect($request->servicios)->map(function ($servicio) {
                    return [
                        'id' => $servicio['id'],
                        'title' => $servicio['titulo'],
                        'description' => $servicio['descripcion'] ?? 'Servicio solicitado',
                        'quantity' => $servicio['cantidad'] ?? 1,
                        'price' => $servicio['precio'],
                        'currency_id' => 'ARS'
                    ];
                })->toArray()
            );

            // Preparar información del pagador si está disponible
            $payer = null;
            if (isset($request->cliente)) {
                $payer = MercadoPagoApiService::createPayer([
                    'name' => $request->cliente['nombre'] ?? '',
                    'surname' => $request->cliente['apellido'] ?? '',
                    'email' => $request->cliente['email'],
                    'phone' => [
                        'area_code' => $request->cliente['telefono']['codigo_area'] ?? '',
                        'number' => $request->cliente['telefono']['numero'] ?? ''
                    ],
                    'identification' => [
                        'type' => $request->cliente['identificacion']['tipo'] ?? 'DNI',
                        'number' => $request->cliente['identificacion']['numero'] ?? ''
                    ],
                    'address' => [
                        'street_name' => $request->cliente['direccion']['calle'] ?? '',
                        'street_number' => $request->cliente['direccion']['numero'] ?? null,
                        'zip_code' => $request->cliente['direccion']['codigo_postal'] ?? ''
                    ]
                ]);
            }

            // Obtener URLs válidas para el entorno actual
            $urls = MercadoPagoApiService::getValidUrls();

            // Preparar datos de la preferencia
            $preferenceData = [
                'items' => $items,
                'back_urls' => $urls,
                'external_reference' => $request->referencia_externa ?? 'servicio-' . time(),
                'notification_url' => $urls['webhook']
            ];

            if ($payer) {
                $preferenceData['payer'] = $payer;
            }

            // Crear la preferencia usando la API
            $result = $this->mercadoPagoApiService->createPreference($preferenceData);

            if ($result['success']) {
                Log::info('Preferencia API creada exitosamente', [
                    'preference_id' => $result['preference_id'],
                    'external_reference' => $preferenceData['external_reference']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Preferencia creada exitosamente',
                    'data' => [
                        'preference_id' => $result['preference_id'],
                        'init_point' => $result['init_point'],
                        'sandbox_init_point' => $result['sandbox_init_point']
                    ]
                ]);
            } else {
                Log::error('Error creando preferencia API', $result);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la preferencia de pago',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en createPreference API', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de un pago
     */
    public function getPayment(string $paymentId): JsonResponse
    {
        try {
            $result = $this->mercadoPagoApiService->getPayment($paymentId);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener información del pago',
                    'error' => $result['error']
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error en getPayment API', [
                'payment_id' => $paymentId,
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
     * Crear un pago directo (sin preferencia)
     */
    public function createDirectPayment(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'monto' => 'required|numeric|min:0',
                'descripcion' => 'required|string',
                'metodo_pago' => 'required|string',
                'email_pagador' => 'required|email',
                'referencia_externa' => 'nullable|string'
            ]);

            $paymentData = [
                'transaction_amount' => floatval($request->monto),
                'description' => $request->descripcion,
                'payment_method_id' => $request->metodo_pago,
                'external_reference' => $request->referencia_externa ?? 'pago-directo-' . time(),
                'payer' => [
                    'email' => $request->email_pagador,
                    'first_name' => $request->nombre_pagador ?? '',
                    'last_name' => $request->apellido_pagador ?? ''
                ]
            ];

            // Agregar información adicional si está disponible
            if ($request->has('identificacion_pagador')) {
                $paymentData['payer']['identification'] = [
                    'type' => $request->identificacion_pagador['tipo'] ?? 'DNI',
                    'number' => $request->identificacion_pagador['numero']
                ];
            }

            $result = $this->mercadoPagoApiService->createPayment($paymentData);

            if ($result['success']) {
                Log::info('Pago directo API creado exitosamente', [
                    'payment_id' => $result['payment_id'],
                    'status' => $result['status']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Pago creado exitosamente',
                    'data' => [
                        'payment_id' => $result['payment_id'],
                        'status' => $result['status'],
                        'payment_data' => $result['data']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el pago',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en createDirectPayment API', [
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
     * Obtener métodos de pago disponibles
     */
    public function getPaymentMethods(): JsonResponse
    {
        try {
            $result = $this->mercadoPagoApiService->getPaymentMethods();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener métodos de pago',
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error en getPaymentMethods API', [
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
     * Validar credenciales de MercadoPago
     */
    public function validateCredentials(): JsonResponse
    {
        try {
            $result = $this->mercadoPagoApiService->validateCredentials();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error en validateCredentials API', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error validando credenciales',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}