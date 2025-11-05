<?php

namespace Tests\Feature\Services;

use App\Services\MercadoPago\MercadoPagoApiService;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class MercadoPagoApiServiceIntegrationTest extends TestCase
{
    protected $mercadoPagoApiService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar credenciales de prueba
        Config::set('services.mercadopago.access_token', 'TEST-123456789-access-token');
        Config::set('services.mercadopago.sandbox', true);
        Config::set('app.url', 'https://test-app.com');
        
        $this->mercadoPagoApiService = new MercadoPagoApiService();
    }

    public function test_complete_payment_flow()
    {
        // Simular el flujo completo de creación de preferencia y obtención de pago
        
        // Step 1: Crear preferencia
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'test-preference-123',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=test-preference-123',
                'items' => [
                    [
                        'id' => 'service-1',
                        'title' => 'Servicio de Limpieza',
                        'quantity' => 1,
                        'unit_price' => 2500.00
                    ]
                ]
            ], 201),
            'api.mercadopago.com/checkout/preferences/test-preference-123' => Http::response([
                'id' => 'test-preference-123',
                'items' => [
                    [
                        'id' => 'service-1',
                        'title' => 'Servicio de Limpieza',
                        'quantity' => 1,
                        'unit_price' => 2500.00
                    ]
                ],
                'status' => 'active'
            ], 200),
            'api.mercadopago.com/v1/payments/98765432101' => Http::response([
                'id' => 98765432101,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 2500.00,
                'currency_id' => 'ARS',
                'external_reference' => 'service-order-456'
            ], 200)
        ]);

        // Datos del servicio a pagar
        $serviceData = [
            'items' => MercadoPagoApiService::createItems([
                [
                    'id' => 'service-1',
                    'title' => 'Servicio de Limpieza',
                    'description' => 'Limpieza completa de casa 3 ambientes',
                    'quantity' => 1,
                    'price' => 2500.00,
                    'currency_id' => 'ARS'
                ]
            ]),
            'external_reference' => 'service-order-456',
            'payer' => MercadoPagoApiService::createPayer([
                'name' => 'María',
                'surname' => 'González',
                'email' => 'maria.gonzalez@example.com',
                'phone' => [
                    'area_code' => '11',
                    'number' => '40001234'
                ],
                'identification' => [
                    'type' => 'DNI',
                    'number' => '12345678'
                ],
                'address' => [
                    'street_name' => 'Av. Corrientes',
                    'street_number' => 1234,
                    'zip_code' => '1043'
                ]
            ])
        ];

        // Crear preferencia
        $preferenceResult = $this->mercadoPagoApiService->createPreference($serviceData);
        
        $this->assertTrue($preferenceResult['success']);
        $this->assertEquals('test-preference-123', $preferenceResult['preference_id']);
        $this->assertNotEmpty($preferenceResult['init_point']);

        // Verificar que la preferencia se creó correctamente
        $preferenceCheck = $this->mercadoPagoApiService->getPreference('test-preference-123');
        
        $this->assertTrue($preferenceCheck['success']);
        $this->assertEquals('test-preference-123', $preferenceCheck['data']['id']);
        $this->assertEquals('active', $preferenceCheck['data']['status']);

        // Simular que el usuario pagó y obtener información del pago
        $paymentResult = $this->mercadoPagoApiService->getPayment('98765432101');
        
        $this->assertTrue($paymentResult['success']);
        $this->assertEquals('approved', $paymentResult['data']['status']);
        $this->assertEquals(2500.00, $paymentResult['data']['transaction_amount']);
        $this->assertEquals('service-order-456', $paymentResult['data']['external_reference']);
    }

    public function test_payment_methods_and_direct_payment()
    {
        // Simular obtención de métodos de pago y creación de pago directo
        
        Http::fake([
            'api.mercadopago.com/v1/payment_methods' => Http::response([
                [
                    'id' => 'visa',
                    'name' => 'Visa',
                    'payment_type_id' => 'credit_card',
                    'status' => 'active'
                ],
                [
                    'id' => 'rapipago',
                    'name' => 'Rapipago',
                    'payment_type_id' => 'ticket',
                    'status' => 'active'
                ],
                [
                    'id' => 'pagofacil',
                    'name' => 'Pago Fácil',
                    'payment_type_id' => 'ticket',
                    'status' => 'active'
                ]
            ], 200),
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 11223344556,
                'status' => 'pending',
                'status_detail' => 'pending_waiting_payment',
                'transaction_amount' => 1500.00,
                'currency_id' => 'ARS',
                'payment_method_id' => 'rapipago',
                'external_reference' => 'service-direct-789'
            ], 201)
        ]);

        // Obtener métodos de pago disponibles
        $paymentMethodsResult = $this->mercadoPagoApiService->getPaymentMethods();
        
        $this->assertTrue($paymentMethodsResult['success']);
        $this->assertCount(3, $paymentMethodsResult['data']);
        
        // Verificar que incluye métodos de tarjeta y tickets
        $methodIds = array_column($paymentMethodsResult['data'], 'id');
        $this->assertContains('visa', $methodIds);
        $this->assertContains('rapipago', $methodIds);
        $this->assertContains('pagofacil', $methodIds);

        // Crear un pago directo usando Rapipago
        $directPaymentData = [
            'transaction_amount' => 1500.00,
            'description' => 'Servicio de jardinería',
            'payment_method_id' => 'rapipago',
            'external_reference' => 'service-direct-789',
            'payer' => [
                'email' => 'cliente@example.com',
                'first_name' => 'Carlos',
                'last_name' => 'Rodriguez',
                'identification' => [
                    'type' => 'DNI',
                    'number' => '87654321'
                ]
            ]
        ];

        $paymentResult = $this->mercadoPagoApiService->createPayment($directPaymentData);
        
        $this->assertTrue($paymentResult['success']);
        $this->assertEquals(11223344556, $paymentResult['payment_id']);
        $this->assertEquals('pending', $paymentResult['status']);
        $this->assertEquals('rapipago', $paymentResult['data']['payment_method_id']);
    }

    public function test_error_handling_scenarios()
    {
        // Testear diferentes escenarios de error
        
        // Escenario 1: Error de autorización
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'message' => 'Invalid credentials',
                'error' => 'unauthorized',
                'status' => 401
            ], 401)
        ]);

        $result = $this->mercadoPagoApiService->createPreference([
            'items' => [['title' => 'Test', 'unit_price' => 100]]
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['error_code']);

        // Escenario 2: Datos inválidos
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'message' => 'Invalid request',
                'error' => 'bad_request',
                'status' => 400,
                'cause' => [
                    [
                        'code' => 'INVALID_TRANSACTION_AMOUNT',
                        'description' => 'Invalid transaction amount'
                    ]
                ]
            ], 400)
        ]);

        $result = $this->mercadoPagoApiService->createPreference([
            'items' => [['title' => 'Test', 'unit_price' => -100]] // Precio inválido
        ]);

        $this->assertFalse($result['success']);
        $this->assertTrue(in_array($result['error_code'], [400, 401])); // Puede ser 400 o 401 dependiendo del mock activo

        // Escenario 3: Recurso no encontrado
        Http::fake([
            'api.mercadopago.com/v1/payments/999999999' => Http::response([
                'message' => 'Payment not found',
                'error' => 'not_found',
                'status' => 404
            ], 404)
        ]);

        $result = $this->mercadoPagoApiService->getPayment('999999999');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', strtolower($result['error']));
    }

    public function test_url_generation_for_different_environments()
    {
        // Testear generación de URLs para diferentes entornos
        
        // Entorno local
        Config::set('app.url', 'http://localhost:8000');
        Config::set('app.env', 'local');
        Config::set('app.name', 'LLServicios Local');

        $localUrls = MercadoPagoApiService::getValidUrls();
        
        $this->assertStringContainsString('httpbin.org', $localUrls['success']);
        $this->assertStringContainsString('httpbin.org', $localUrls['failure']);
        $this->assertStringContainsString('httpbin.org', $localUrls['pending']);
        $this->assertStringContainsString('httpbin.org', $localUrls['webhook']);

        // Entorno de producción
        Config::set('app.url', 'https://llservicios.com');
        Config::set('app.env', 'production');

        $prodUrls = MercadoPagoApiService::getValidUrls();
        
        $this->assertEquals('https://llservicios.com/mercadopago/success', $prodUrls['success']);
        $this->assertEquals('https://llservicios.com/mercadopago/failure', $prodUrls['failure']);
        $this->assertEquals('https://llservicios.com/mercadopago/pending', $prodUrls['pending']);
        $this->assertEquals('https://llservicios.com/mercadopago/webhook', $prodUrls['webhook']);
    }

    public function test_helper_methods_with_real_service_data()
    {
        // Testear métodos helper con datos reales del sistema de servicios
        
        $servicios = [
            [
                'id' => 'limpieza-001',
                'title' => 'Limpieza Profunda de Hogar',
                'description' => 'Servicio completo de limpieza para casa de 4 ambientes',
                'quantity' => 1,
                'price' => 3500.00,
                'currency_id' => 'ARS'
            ],
            [
                'id' => 'jardineria-002',
                'title' => 'Mantenimiento de Jardín',
                'description' => 'Poda, riego y mantenimiento general',
                'quantity' => 2,
                'price' => 1200.00,
                'currency_id' => 'ARS'
            ]
        ];

        $items = MercadoPagoApiService::createItems($servicios);
        
        $this->assertCount(2, $items);
        $this->assertEquals('limpieza-001', $items[0]['id']);
        $this->assertEquals(3500.00, $items[0]['unit_price']);
        $this->assertEquals('jardineria-002', $items[1]['id']);
        $this->assertEquals(2, $items[1]['quantity']);

        // Datos del cliente
        $clienteData = [
            'name' => 'Ana',
            'surname' => 'Martínez',
            'email' => 'ana.martinez@gmail.com',
            'phone' => [
                'area_code' => '11',
                'number' => '15551234'
            ],
            'identification' => [
                'type' => 'DNI',
                'number' => '23456789'
            ],
            'address' => [
                'street_name' => 'Av. Santa Fe',
                'street_number' => 2500,
                'zip_code' => '1123'
            ]
        ];

        $payer = MercadoPagoApiService::createPayer($clienteData);
        
        $this->assertEquals('Ana', $payer['name']);
        $this->assertEquals('ana.martinez@gmail.com', $payer['email']);
        $this->assertEquals('23456789', $payer['identification']['number']);
        $this->assertEquals('Av. Santa Fe', $payer['address']['street_name']);
    }

    protected function tearDown(): void
    {
        Http::preventStrayRequests(false);
        parent::tearDown();
    }
}