<?php

namespace Tests\Unit\Services;

use App\Services\MercadoPago\MercadoPagoApiService;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MercadoPagoApiServiceTest extends TestCase
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

    public function test_create_preference_success()
    {
        // Mock de la respuesta exitosa de la API
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'test-preference-id',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=test-preference-id',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=test-preference-id',
                'items' => [
                    [
                        'id' => 'item-1',
                        'title' => 'Test Product',
                        'quantity' => 1,
                        'unit_price' => 100.00
                    ]
                ]
            ], 201)
        ]);

        $data = [
            'items' => [
                [
                    'id' => 'item-1',
                    'title' => 'Test Product',
                    'description' => 'Product for testing',
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'currency_id' => 'ARS'
                ]
            ],
            'external_reference' => 'test-ref-123',
            'payer' => [
                'name' => 'Test',
                'surname' => 'User',
                'email' => 'test@example.com'
            ]
        ];

        $result = $this->mercadoPagoApiService->createPreference($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('test-preference-id', $result['preference_id']);
        $this->assertStringContainsString('test-preference-id', $result['init_point']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_create_preference_failure()
    {
        // Mock de la respuesta de error de la API
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'message' => 'Invalid access token',
                'error' => 'unauthorized',
                'status' => 401
            ], 401)
        ]);

        $data = [
            'items' => [
                [
                    'title' => 'Test Product',
                    'quantity' => 1,
                    'unit_price' => 100.00
                ]
            ]
        ];

        $result = $this->mercadoPagoApiService->createPreference($data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(401, $result['error_code']);
    }

    public function test_get_payment_success()
    {
        $paymentId = '12345678901';
        
        // Mock de la respuesta exitosa
        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'id' => $paymentId,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 100.00,
                'currency_id' => 'ARS'
            ], 200)
        ]);

        $result = $this->mercadoPagoApiService->getPayment($paymentId);

        $this->assertTrue($result['success']);
        $this->assertEquals($paymentId, $result['data']['id']);
        $this->assertEquals('approved', $result['data']['status']);
    }

    public function test_get_payment_not_found()
    {
        $paymentId = 'non-existent-payment';
        
        // Mock de respuesta de pago no encontrado
        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'message' => 'Payment not found',
                'error' => 'not_found',
                'status' => 404
            ], 404)
        ]);

        $result = $this->mercadoPagoApiService->getPayment($paymentId);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_get_preference_success()
    {
        $preferenceId = 'test-preference-id';
        
        // Mock de la respuesta exitosa
        Http::fake([
            "api.mercadopago.com/checkout/preferences/{$preferenceId}" => Http::response([
                'id' => $preferenceId,
                'items' => [
                    [
                        'id' => 'item-1',
                        'title' => 'Test Product',
                        'quantity' => 1,
                        'unit_price' => 100.00
                    ]
                ],
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=' . $preferenceId
            ], 200)
        ]);

        $result = $this->mercadoPagoApiService->getPreference($preferenceId);

        $this->assertTrue($result['success']);
        $this->assertEquals($preferenceId, $result['data']['id']);
        $this->assertArrayHasKey('items', $result['data']);
    }

    public function test_create_payment_success()
    {
        // Mock de la respuesta exitosa para crear un pago
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 12345678901,
                'status' => 'pending',
                'status_detail' => 'pending_waiting_payment',
                'transaction_amount' => 100.00,
                'currency_id' => 'ARS',
                'payment_method_id' => 'rapipago'
            ], 201)
        ]);

        $paymentData = [
            'transaction_amount' => 100.00,
            'description' => 'Test payment',
            'payment_method_id' => 'rapipago',
            'payer' => [
                'email' => 'test@example.com'
            ]
        ];

        $result = $this->mercadoPagoApiService->createPayment($paymentData);

        $this->assertTrue($result['success']);
        $this->assertEquals(12345678901, $result['payment_id']);
        $this->assertEquals('pending', $result['status']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_get_payment_methods_success()
    {
        // Mock de la respuesta exitosa para métodos de pago
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
                ]
            ], 200)
        ]);

        $result = $this->mercadoPagoApiService->getPaymentMethods();

        $this->assertTrue($result['success']);
        $this->assertIsArray($result['data']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('visa', $result['data'][0]['id']);
    }

    public function test_validate_credentials_success()
    {
        // Mock de respuesta exitosa para validación de credenciales
        Http::fake([
            'api.mercadopago.com/v1/payment_methods' => Http::response([
                [
                    'id' => 'visa',
                    'name' => 'Visa',
                    'payment_type_id' => 'credit_card'
                ]
            ], 200)
        ]);

        $result = $this->mercadoPagoApiService->validateCredentials();

        $this->assertTrue($result['valid']);
        $this->assertEquals('Credenciales válidas', $result['message']);
    }

    public function test_validate_credentials_empty_token()
    {
        // Configurar access token vacío
        Config::set('services.mercadopago.access_token', '');
        $service = new MercadoPagoApiService();

        $result = $service->validateCredentials();

        $this->assertFalse($result['valid']);
        $this->assertEquals('Access token no configurado', $result['error']);
    }

    public function test_validate_credentials_invalid_format()
    {
        // Configurar access token con formato inválido
        Config::set('services.mercadopago.access_token', 'invalid-token-format');
        $service = new MercadoPagoApiService();

        $result = $service->validateCredentials();

        $this->assertFalse($result['valid']);
        $this->assertEquals('Formato de access token inválido', $result['error']);
    }

    public function test_validate_credentials_unauthorized()
    {
        // Mock de respuesta no autorizada
        Http::fake([
            'api.mercadopago.com/v1/payment_methods' => Http::response([
                'message' => 'Invalid credentials',
                'error' => 'unauthorized'
            ], 401)
        ]);

        $result = $this->mercadoPagoApiService->validateCredentials();

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Credenciales inválidas', $result['error']);
    }

    public function test_create_items_helper()
    {
        $products = [
            [
                'id' => 'product-1',
                'title' => 'Product 1',
                'description' => 'Description 1',
                'quantity' => 2,
                'price' => 50.00,
                'currency_id' => 'ARS'
            ],
            [
                'title' => 'Product 2',
                'unit_price' => 75.50
            ]
        ];

        $items = MercadoPagoApiService::createItems($products);

        $this->assertCount(2, $items);
        $this->assertEquals('product-1', $items[0]['id']);
        $this->assertEquals('Product 1', $items[0]['title']);
        $this->assertEquals(2, $items[0]['quantity']);
        $this->assertEquals(50.00, $items[0]['unit_price']);
        $this->assertEquals('Product 2', $items[1]['title']);
        $this->assertEquals(75.50, $items[1]['unit_price']);
        $this->assertEquals(1, $items[1]['quantity']); // Default value
    }

    public function test_create_payer_helper()
    {
        $payerData = [
            'name' => 'John',
            'surname' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => [
                'area_code' => '11',
                'number' => '12345678'
            ],
            'identification' => [
                'type' => 'DNI',
                'number' => '12345678'
            ],
            'address' => [
                'street_name' => 'Main Street',
                'street_number' => 123,
                'zip_code' => '1234'
            ]
        ];

        $payer = MercadoPagoApiService::createPayer($payerData);

        $this->assertEquals('John', $payer['name']);
        $this->assertEquals('Doe', $payer['surname']);
        $this->assertEquals('john.doe@example.com', $payer['email']);
        $this->assertEquals('11', $payer['phone']['area_code']);
        $this->assertEquals('DNI', $payer['identification']['type']);
        $this->assertEquals('Main Street', $payer['address']['street_name']);
    }

    public function test_get_valid_urls_localhost()
    {
        Config::set('app.url', 'http://localhost:8000');
        Config::set('app.env', 'local');
        Config::set('app.name', 'Test App');

        $urls = MercadoPagoApiService::getValidUrls();

        $this->assertStringContainsString('httpbin.org', $urls['success']);
        $this->assertStringContainsString('success=true', $urls['success']);
        $this->assertStringContainsString('httpbin.org', $urls['webhook']);
    }

    public function test_get_valid_urls_production()
    {
        Config::set('app.url', 'https://myapp.com');
        Config::set('app.env', 'production');

        $urls = MercadoPagoApiService::getValidUrls();

        $this->assertEquals('https://myapp.com/mercadopago/success', $urls['success']);
        $this->assertEquals('https://myapp.com/mercadopago/failure', $urls['failure']);
        $this->assertEquals('https://myapp.com/mercadopago/pending', $urls['pending']);
        $this->assertEquals('https://myapp.com/mercadopago/webhook', $urls['webhook']);
    }

    protected function tearDown(): void
    {
        Http::preventStrayRequests(false);
        parent::tearDown();
    }
}