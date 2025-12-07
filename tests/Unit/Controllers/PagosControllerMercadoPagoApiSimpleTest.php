<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\PagosController;
use App\Services\MercadoPago\MercadoPagoApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

class PagosControllerMercadoPagoApiSimpleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar credenciales de prueba
        Config::set('services.mercadopago.access_token', 'TEST-123456789-access-token');
        Config::set('services.mercadopago.sandbox', true);
        Config::set('app.url', 'https://test-app.com');
    }

    public function test_obtener_info_pago_exitoso()
    {
        $paymentId = '12345678901';
        
        // Mock de respuesta exitosa para obtener información de pago
        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'id' => $paymentId,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 2500.00,
                'currency_id' => 'ARS',
                'external_reference' => 'servicio_pagar_123'
            ], 200)
        ]);

        $controller = new PagosController();
        $response = $controller->obtenerInfoPago($paymentId);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($paymentId, $responseData['data']['id']);
        $this->assertEquals('approved', $responseData['data']['status']);
        $this->assertEquals(2500.00, $responseData['data']['transaction_amount']);
    }

    public function test_obtener_info_pago_no_encontrado()
    {
        $paymentId = 'payment-not-found';
        
        // Mock de respuesta de pago no encontrado
        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'message' => 'Payment not found',
                'error' => 'not_found'
            ], 404)
        ]);

        $controller = new PagosController();
        $response = $controller->obtenerInfoPago($paymentId);

        $this->assertEquals(404, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function test_obtener_info_pago_error_servidor()
    {
        $paymentId = 'payment-error';
        
        // Mock de error de servidor
        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'message' => 'Internal server error'
            ], 500)
        ]);

        $controller = new PagosController();
        $response = $controller->obtenerInfoPago($paymentId);

        $this->assertEquals(404, $response->getStatusCode()); // El servicio maneja 500 como 404
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
    }

    public function test_helpers_mercadopago_api_service()
    {
        // Test del helper createItems
        $servicios = [
            [
                'id' => 'servicio_123',
                'title' => 'Servicio de Limpieza',
                'description' => 'Limpieza completa',
                'quantity' => 1,
                'price' => 2500.00,
                'currency_id' => 'ARS'
            ],
            [
                'id' => 'servicio_456',
                'title' => 'Servicio de Jardinería',
                'description' => 'Mantenimiento de jardín',
                'quantity' => 2,
                'price' => 1200.00,
                'currency_id' => 'ARS'
            ]
        ];

        $items = MercadoPagoApiService::createItems($servicios);
        
        $this->assertCount(2, $items);
        $this->assertEquals('servicio_123', $items[0]['id']);
        $this->assertEquals('Servicio de Limpieza', $items[0]['title']);
        $this->assertEquals(2500.00, $items[0]['unit_price']);
        $this->assertEquals(1, $items[0]['quantity']);
        
        $this->assertEquals('servicio_456', $items[1]['id']);
        $this->assertEquals(2, $items[1]['quantity']);
        $this->assertEquals(1200.00, $items[1]['unit_price']);

        // Test del helper createPayer
        $clienteData = [
            'name' => 'Juan',
            'surname' => 'Pérez',
            'email' => 'juan.perez@test.com',
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
        ];

        $payer = MercadoPagoApiService::createPayer($clienteData);
        
        $this->assertEquals('Juan', $payer['name']);
        $this->assertEquals('Pérez', $payer['surname']);
        $this->assertEquals('juan.perez@test.com', $payer['email']);
        $this->assertEquals('11', $payer['phone']['area_code']);
        $this->assertEquals('40001234', $payer['phone']['number']);
        $this->assertEquals('DNI', $payer['identification']['type']);
        $this->assertEquals('12345678', $payer['identification']['number']);
        $this->assertEquals('Av. Corrientes', $payer['address']['street_name']);
        $this->assertEquals(1234, $payer['address']['street_number']);
    }

    public function test_helper_create_items_con_datos_minimos()
    {
        // Test con datos mínimos requeridos
        $servicios = [
            [
                'title' => 'Servicio Básico',
                'price' => 1000.00
            ]
        ];

        $items = MercadoPagoApiService::createItems($servicios);
        
        $this->assertCount(1, $items);
        $this->assertNull($items[0]['id']); // id opcional
        $this->assertEquals('Servicio Básico', $items[0]['title']);
        $this->assertEquals('', $items[0]['description']); // descripción por defecto
        $this->assertEquals(1, $items[0]['quantity']); // cantidad por defecto
        $this->assertEquals(1000.00, $items[0]['unit_price']);
        $this->assertEquals('ARS', $items[0]['currency_id']); // moneda por defecto
    }

    public function test_helper_create_payer_con_datos_minimos()
    {
        // Test con datos mínimos requeridos
        $clienteData = [
            'email' => 'cliente@test.com'
        ];

        $payer = MercadoPagoApiService::createPayer($clienteData);
        
        $this->assertEquals('', $payer['name']); // nombre por defecto
        $this->assertEquals('', $payer['surname']); // apellido por defecto
        $this->assertEquals('cliente@test.com', $payer['email']);
        $this->assertEquals('', $payer['phone']['area_code']); // por defecto
        $this->assertEquals('', $payer['phone']['number']); // por defecto
        $this->assertEquals('DNI', $payer['identification']['type']); // por defecto
        $this->assertEquals('', $payer['identification']['number']); // por defecto
        $this->assertEquals('', $payer['address']['street_name']); // por defecto
        $this->assertNull($payer['address']['street_number']); // por defecto
        $this->assertEquals('', $payer['address']['zip_code']); // por defecto
    }

    public function test_helper_get_valid_urls_localhost()
    {
        Config::set('app.url', 'http://localhost:8000');
        Config::set('app.env', 'local');
        Config::set('app.name', 'LLServicios Test');

        $urls = MercadoPagoApiService::getValidUrls();
        
        $this->assertStringContainsString('httpbin.org', $urls['success']);
        $this->assertStringContainsString('success=true', $urls['success']);
        $this->assertStringContainsString('LLServicios', $urls['success']);
        
        $this->assertStringContainsString('httpbin.org', $urls['failure']);
        $this->assertStringContainsString('failure=true', $urls['failure']);
        
        $this->assertStringContainsString('httpbin.org', $urls['pending']);
        $this->assertStringContainsString('pending=true', $urls['pending']);
        
        $this->assertEquals('https://httpbin.org/post', $urls['webhook']);
    }

    public function test_helper_get_valid_urls_production()
    {
        Config::set('app.url', 'https://llservicios.com');
        Config::set('app.env', 'production');

        $urls = MercadoPagoApiService::getValidUrls();
        
        $this->assertEquals('https://llservicios.com/mercadopago/success', $urls['success']);
        $this->assertEquals('https://llservicios.com/mercadopago/failure', $urls['failure']);
        $this->assertEquals('https://llservicios.com/mercadopago/pending', $urls['pending']);
        $this->assertEquals('https://llservicios.com/mercadopago/webhook', $urls['webhook']);
    }

    protected function tearDown(): void
    {
        Http::preventStrayRequests(false);
        parent::tearDown();
    }
}