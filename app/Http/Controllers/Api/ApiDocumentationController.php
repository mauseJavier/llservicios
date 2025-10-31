<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    /**
     * Obtener la documentación completa de la API
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $documentation = [
            'api_name' => config('app.name') . ' API',
            'version' => '1.0.0',
            'base_url' => url('/api'),
            'description' => 'Documentación de todos los endpoints disponibles en la API',
            'last_updated' => '2025-10-30',
            'endpoints' => $this->getAllEndpoints()
        ];

        return response()->json($documentation, 200);
    }

    /**
     * Obtener documentación de un endpoint específico
     * 
     * @param string $group Grupo del endpoint (ej: cliente, mercadopago, whatsapp)
     * @return JsonResponse
     */
    public function show(string $group): JsonResponse
    {
        $endpoints = $this->getAllEndpoints();
        
        $filtered = array_filter($endpoints, function($endpoint) use ($group) {
            return $endpoint['group'] === $group;
        });

        if (empty($filtered)) {
            return response()->json([
                'success' => false,
                'message' => "No se encontró documentación para el grupo: {$group}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'group' => $group,
            'endpoints' => array_values($filtered)
        ], 200);
    }

    /**
     * Obtener lista de grupos disponibles
     * 
     * @return JsonResponse
     */
    public function groups(): JsonResponse
    {
        $endpoints = $this->getAllEndpoints();
        $groups = [];

        foreach ($endpoints as $endpoint) {
            $groupName = $endpoint['group'];
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name' => $groupName,
                    'count' => 0,
                    'description' => $endpoint['group_description'] ?? ''
                ];
            }
            $groups[$groupName]['count']++;
        }

        return response()->json([
            'success' => true,
            'groups' => array_values($groups)
        ], 200);
    }

    /**
     * Obtener todos los endpoints documentados
     * 
     * @return array
     */
    private function getAllEndpoints(): array
    {
        return [
            // ============================================================
            // GRUPO: CLIENTES
            // ============================================================
            [
                'group' => 'cliente',
                'group_description' => 'Endpoints relacionados con la gestión de clientes',
                'name' => 'Buscar Cliente',
                'method' => 'GET',
                'endpoint' => '/api/cliente/buscar',
                'description' => 'Busca un cliente por DNI, correo o nombre y devuelve sus servicios pagos e impagos',
                'authentication' => false,
                'parameters' => [
                    'query' => [
                        [
                            'name' => 'dni',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'DNI del cliente',
                            'example' => '12345678'
                        ],
                        [
                            'name' => 'correo',
                            'type' => 'email',
                            'required' => false,
                            'description' => 'Correo electrónico del cliente',
                            'example' => 'cliente@email.com'
                        ],
                        [
                            'name' => 'nombre',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Nombre del cliente (búsqueda parcial)',
                            'example' => 'Juan'
                        ],
                        [
                            'name' => 'empresa_id',
                            'type' => 'integer',
                            'required' => false,
                            'description' => 'ID de la empresa para filtrar servicios',
                            'example' => '1'
                        ],
                        [
                            'name' => 'nombre_empresa',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Nombre de la empresa para filtrar servicios',
                            'example' => 'MiEmpresa'
                        ]
                    ]
                ],
                'request_example' => [
                    'curl' => 'curl -X GET "' . url('/api') . '/cliente/buscar?dni=12345678&correo=cliente@email.com"',
                    'url' => url('/api') . '/cliente/buscar?dni=12345678&correo=cliente@email.com&nombre=Juan'
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'success' => true,
                        'data' => [
                            'cliente' => [
                                'id' => 1,
                                'nombre' => 'Juan Pérez',
                                'dni' => '12345678',
                                'correo' => 'cliente@email.com',
                                'telefono' => '5492942506803',
                                'direccion' => 'Calle Falsa 123'
                            ],
                            'estado_cliente' => true,
                            'servicios_pagos' => [
                                [
                                    'id' => 1,
                                    'servicio_id' => 5,
                                    'servicio_nombre' => 'Internet 50MB',
                                    'empresa_id' => 1,
                                    'empresa_nombre' => 'MiEmpresa',
                                    'cantidad' => 1,
                                    'precio' => '5000.00',
                                    'total' => '5000.00',
                                    'estado' => 'pago',
                                    'fecha_vencimiento' => '2025-10-15',
                                    'periodo_servicio' => '2025-10',
                                    'mp_payment_id' => '123456789',
                                    'comentario' => null,
                                    'created_at' => '2025-10-01T10:00:00.000000Z',
                                    'updated_at' => '2025-10-15T14:30:00.000000Z'
                                ]
                            ],
                            'servicios_impagos' => [],
                            'resumen' => [
                                'total_pagos' => 1,
                                'total_impagos' => 0,
                                'monto_total_impagos' => 0
                            ]
                        ]
                    ]
                ],
                'response_error' => [
                    'code' => 404,
                    'example' => [
                        'success' => false,
                        'message' => 'Cliente no encontrado'
                    ]
                ],
                'notes' => [
                    'Debe proporcionar al menos uno de los parámetros: dni, correo o nombre',
                    'Los filtros de empresa son opcionales y se aplican sobre los servicios',
                    'El estado_cliente es false si tiene servicios impagos',
                    'Las búsquedas por nombre y correo son parciales (LIKE)'
                ]
            ],

            // ============================================================
            // GRUPO: MERCADOPAGO
            // ============================================================
            [
                'group' => 'mercadopago',
                'group_description' => 'Endpoints para integración con MercadoPago',
                'name' => 'Crear Preferencia de Pago',
                'method' => 'POST',
                'endpoint' => '/api/mercadopago-api/preference',
                'description' => 'Crea una preferencia de pago en MercadoPago para Checkout Pro',
                'authentication' => false,
                'parameters' => [
                    'body' => [
                        [
                            'name' => 'items',
                            'type' => 'array',
                            'required' => true,
                            'description' => 'Lista de items a pagar',
                            'example' => [
                                [
                                    'title' => 'Servicio de Internet',
                                    'quantity' => 1,
                                    'unit_price' => 5000,
                                    'currency_id' => 'ARS'
                                ]
                            ]
                        ],
                        [
                            'name' => 'external_reference',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Referencia externa para identificar el pago',
                            'example' => 'ORDER-123'
                        ],
                        [
                            'name' => 'payer',
                            'type' => 'object',
                            'required' => false,
                            'description' => 'Información del pagador',
                            'example' => [
                                'email' => 'cliente@email.com',
                                'name' => 'Juan',
                                'surname' => 'Pérez'
                            ]
                        ]
                    ]
                ],
                'request_example' => [
                    'curl' => 'curl -X POST "' . url('/api') . '/mercadopago-api/preference" -H "Content-Type: application/json" -d \'{"items":[{"title":"Servicio","quantity":1,"unit_price":5000}]}\'',
                    'json' => [
                        'items' => [
                            [
                                'title' => 'Servicio de Internet',
                                'quantity' => 1,
                                'unit_price' => 5000,
                                'currency_id' => 'ARS'
                            ]
                        ],
                        'external_reference' => 'ORDER-123',
                        'payer' => [
                            'email' => 'cliente@email.com'
                        ]
                    ]
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'success' => true,
                        'preference_id' => '123456789-abcd-efgh',
                        'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=...',
                        'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=...'
                    ]
                ],
                'response_error' => [
                    'code' => 500,
                    'example' => [
                        'success' => false,
                        'error' => 'Error al crear la preferencia',
                        'data' => null
                    ]
                ],
                'notes' => [
                    'Requiere configuración previa de credenciales de MercadoPago en .env',
                    'El init_point es la URL para redirigir al usuario al checkout',
                    'En modo sandbox, usar sandbox_init_point para pruebas'
                ]
            ],

            [
                'group' => 'mercadopago',
                'group_description' => 'Endpoints para integración con MercadoPago',
                'name' => 'Obtener Información de Pago',
                'method' => 'GET',
                'endpoint' => '/api/mercadopago-api/payment/{paymentId}',
                'description' => 'Obtiene la información detallada de un pago específico',
                'authentication' => false,
                'parameters' => [
                    'path' => [
                        [
                            'name' => 'paymentId',
                            'type' => 'integer',
                            'required' => true,
                            'description' => 'ID del pago en MercadoPago',
                            'example' => '123456789'
                        ]
                    ]
                ],
                'request_example' => [
                    'curl' => 'curl -X GET "' . url('/api') . '/mercadopago-api/payment/123456789"',
                    'url' => url('/api') . '/mercadopago-api/payment/123456789'
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'success' => true,
                        'data' => [
                            'id' => 123456789,
                            'status' => 'approved',
                            'status_detail' => 'accredited',
                            'transaction_amount' => 5000,
                            'date_approved' => '2025-10-30T15:30:00.000Z'
                        ]
                    ]
                ],
                'notes' => [
                    'El paymentId se obtiene del webhook o de la respuesta de pago'
                ]
            ],

            [
                'group' => 'mercadopago',
                'group_description' => 'Endpoints para integración con MercadoPago',
                'name' => 'Validar Credenciales',
                'method' => 'GET',
                'endpoint' => '/api/mercadopago-api/validate-credentials',
                'description' => 'Valida que las credenciales de MercadoPago estén configuradas correctamente',
                'authentication' => false,
                'parameters' => [],
                'request_example' => [
                    'curl' => 'curl -X GET "' . url('/api') . '/mercadopago-api/validate-credentials"',
                    'url' => url('/api') . '/mercadopago-api/validate-credentials'
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'valid' => true,
                        'message' => 'Credenciales válidas'
                    ]
                ],
                'notes' => [
                    'Útil para verificar la configuración antes de realizar operaciones'
                ]
            ],

            // ============================================================
            // GRUPO: WHATSAPP
            // ============================================================
            [
                'group' => 'whatsapp',
                'group_description' => 'Endpoints para envío de mensajes por WhatsApp',
                'name' => 'Enviar Mensaje de Texto',
                'method' => 'POST',
                'endpoint' => '/api/whatsapp/send-text',
                'description' => 'Envía un mensaje de texto simple por WhatsApp',
                'authentication' => false,
                'parameters' => [
                    'body' => [
                        [
                            'name' => 'phone',
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Número de teléfono con código de país',
                            'example' => '5492942506803'
                        ],
                        [
                            'name' => 'message',
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Mensaje de texto a enviar',
                            'example' => 'Hola! Este es un mensaje de prueba'
                        ],
                        [
                            'name' => 'pushName',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Nombre que aparecerá como remitente',
                            'example' => 'Mi Empresa'
                        ]
                    ]
                ],
                'request_example' => [
                    'curl' => 'curl -X POST "' . url('/api') . '/whatsapp/send-text" -H "Content-Type: application/json" -d \'{"phone":"5492942506803","message":"Hola!"}\'',
                    'json' => [
                        'phone' => '5492942506803',
                        'message' => 'Hola! Este es un mensaje de prueba'
                    ]
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'success' => true,
                        'data' => [],
                        'message' => 'Mensaje enviado correctamente'
                    ]
                ],
                'response_error' => [
                    'code' => 500,
                    'example' => [
                        'success' => false,
                        'error' => 'Error al enviar el mensaje',
                        'data' => null
                    ]
                ],
                'notes' => [
                    'Requiere configuración de WhatsApp en .env',
                    'El número debe incluir código de país',
                    'Se recomienda usar Jobs para envíos masivos'
                ]
            ],

            [
                'group' => 'whatsapp',
                'group_description' => 'Endpoints para envío de mensajes por WhatsApp',
                'name' => 'Enviar Documento',
                'method' => 'POST',
                'endpoint' => '/api/whatsapp/send-document',
                'description' => 'Envía un documento (PDF, Word, etc.) por WhatsApp',
                'authentication' => false,
                'parameters' => [
                    'body' => [
                        [
                            'name' => 'phone',
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Número de teléfono con código de país',
                            'example' => '5492942506803'
                        ],
                        [
                            'name' => 'document_url',
                            'type' => 'url',
                            'required' => true,
                            'description' => 'URL pública del documento',
                            'example' => 'https://ejemplo.com/documento.pdf'
                        ],
                        [
                            'name' => 'filename',
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Nombre del archivo',
                            'example' => 'documento.pdf'
                        ],
                        [
                            'name' => 'caption',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Texto que acompaña al documento',
                            'example' => 'Aquí está tu documento'
                        ]
                    ]
                ],
                'request_example' => [
                    'curl' => 'curl -X POST "' . url('/api') . '/whatsapp/send-document" -H "Content-Type: application/json" -d \'{"phone":"5492942506803","document_url":"https://ejemplo.com/doc.pdf","filename":"documento.pdf"}\'',
                    'json' => [
                        'phone' => '5492942506803',
                        'document_url' => 'https://ejemplo.com/documento.pdf',
                        'filename' => 'documento.pdf',
                        'caption' => 'Aquí está tu documento'
                    ]
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'success' => true,
                        'data' => [],
                        'message' => 'Documento enviado correctamente'
                    ]
                ],
                'notes' => [
                    'La URL del documento debe ser públicamente accesible',
                    'Soporta PDF, Word, Excel, y otros formatos'
                ]
            ],

            [
                'group' => 'whatsapp',
                'group_description' => 'Endpoints para envío de mensajes por WhatsApp',
                'name' => 'Validar Configuración',
                'method' => 'GET',
                'endpoint' => '/api/whatsapp/validate-config',
                'description' => 'Valida que el servicio de WhatsApp esté configurado correctamente',
                'authentication' => false,
                'parameters' => [],
                'request_example' => [
                    'curl' => 'curl -X GET "' . url('/api') . '/whatsapp/validate-config"',
                    'url' => url('/api') . '/whatsapp/validate-config'
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'valid' => true,
                        'errors' => [],
                        'config' => [
                            'api_url' => '***',
                            'instance_id' => '***',
                            'api_key_set' => true
                        ]
                    ]
                ],
                'notes' => [
                    'Útil para verificar la configuración antes de enviar mensajes'
                ]
            ],

            // ============================================================
            // GRUPO: DOCUMENTACIÓN
            // ============================================================
            [
                'group' => 'documentation',
                'group_description' => 'Endpoints de documentación de la API',
                'name' => 'Obtener Documentación Completa',
                'method' => 'GET',
                'endpoint' => '/api/docs',
                'description' => 'Obtiene la documentación completa de todos los endpoints',
                'authentication' => false,
                'parameters' => [],
                'request_example' => [
                    'curl' => 'curl -X GET "' . url('/api') . '/docs"',
                    'url' => url('/api') . '/docs'
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'api_name' => 'API Documentation',
                        'version' => '1.0.0',
                        'endpoints' => '...'
                    ]
                ],
                'notes' => [
                    'Este endpoint devuelve la documentación que estás viendo ahora'
                ]
            ],

            [
                'group' => 'documentation',
                'group_description' => 'Endpoints de documentación de la API',
                'name' => 'Obtener Documentación por Grupo',
                'method' => 'GET',
                'endpoint' => '/api/docs/{group}',
                'description' => 'Obtiene la documentación de un grupo específico de endpoints',
                'authentication' => false,
                'parameters' => [
                    'path' => [
                        [
                            'name' => 'group',
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Nombre del grupo (cliente, mercadopago, whatsapp, etc.)',
                            'example' => 'cliente'
                        ]
                    ]
                ],
                'request_example' => [
                    'curl' => 'curl -X GET "' . url('/api') . '/docs/cliente"',
                    'url' => url('/api') . '/docs/cliente'
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'success' => true,
                        'group' => 'cliente',
                        'endpoints' => '...'
                    ]
                ],
                'notes' => [
                    'Útil para obtener solo la documentación de un módulo específico'
                ]
            ],

            [
                'group' => 'documentation',
                'group_description' => 'Endpoints de documentación de la API',
                'name' => 'Listar Grupos Disponibles',
                'method' => 'GET',
                'endpoint' => '/api/docs/groups',
                'description' => 'Lista todos los grupos de endpoints disponibles',
                'authentication' => false,
                'parameters' => [],
                'request_example' => [
                    'curl' => 'curl -X GET "' . url('/api') . '/docs/groups"',
                    'url' => url('/api') . '/docs/groups'
                ],
                'response_success' => [
                    'code' => 200,
                    'example' => [
                        'success' => true,
                        'groups' => [
                            [
                                'name' => 'cliente',
                                'count' => 1,
                                'description' => 'Endpoints relacionados con clientes'
                            ],
                            [
                                'name' => 'mercadopago',
                                'count' => 3,
                                'description' => 'Endpoints para MercadoPago'
                            ]
                        ]
                    ]
                ],
                'notes' => [
                    'Muestra un resumen de todos los grupos disponibles'
                ]
            ]
        ];
    }
}
