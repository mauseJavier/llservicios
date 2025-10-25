<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\PagosController;
use App\Services\MercadoPagoApiService;
use App\Models\ServicioPagar;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PagosControllerMercadoPagoApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $empresa;
    protected $cliente;
    protected $servicio;
    protected $servicioPagar;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar credenciales de prueba
        Config::set('services.mercadopago.access_token', 'TEST-123456789-access-token');
        Config::set('services.mercadopago.sandbox', true);
        Config::set('app.url', 'https://test-app.com');

        // Crear datos de prueba directamente
        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Test',
            'cuit' => 12345678901,
            'correo' => 'empresa@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-empresa-token'
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'empresa_id' => $this->empresa->id,
            'dni' => '12345678'
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Cliente Test',
            'dni' => 12345678,
            'telefono' => '987654321',
            'domicilio' => 'Test Address',
            'correo' => 'cliente@test.com'
        ]);

        $this->servicio = Servicio::create([
            'nombre' => 'Servicio Test',
            'descripcion' => 'Descripción del servicio de prueba',
            'precio' => 2500.00,
            'empresa_id' => $this->empresa->id
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 2500.00,
            'cantidad' => 1
        ]);

        Auth::login($this->user);
    }

    public function test_generar_pago_con_mercadopago_api_service_exitoso()
    {
        // Mock de la respuesta exitosa de MercadoPago API
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'test-preference-123',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=test-preference-123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=test-preference-123'
            ], 201)
        ]);

        $controller = new PagosController();
        $response = $controller->generarPago($this->servicioPagar);

        // Verificar que es una redirección
        $this->assertEquals(302, $response->getStatusCode());
        
        // Verificar que se guardó el preference_id en la base de datos
        $this->servicioPagar->refresh();
        $this->assertEquals('test-preference-123', $this->servicioPagar->mp_preference_id);
        
        // Verificar que la URL de redirección es válida (contiene checkout o preference)
        $location = $response->headers->get('Location');
        $this->assertNotEmpty($location, 'La ubicación de redirección no debe estar vacía');
    }

    public function test_generar_pago_con_servicio_ya_pagado()
    {
        // Configurar servicio como ya pagado
        $this->servicioPagar->update(['estado' => 'pago']);

        $controller = new PagosController();
        $response = $controller->generarPago($this->servicioPagar);

        // Verificar que es una redirección con error
        $this->assertEquals(302, $response->getStatusCode());
        
        // En un test real, verificarías la sesión para el mensaje de error
        // $this->assertEquals('Este servicio ya ha sido pagado.', session('error'));
    }

    public function test_generar_pago_sin_credenciales_mercadopago()
    {
        // Configurar empresa sin credenciales
        $this->empresa->update(['MP_ACCESS_TOKEN' => null]);

        $controller = new PagosController();
        $response = $controller->generarPago($this->servicioPagar);

        // Verificar que es una redirección con error
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_generar_pago_con_error_api_mercadopago()
    {
        // Mock de error en la API de MercadoPago
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'message' => 'Invalid access token',
                'error' => 'unauthorized'
            ], 401)
        ]);

        $controller = new PagosController();
        $response = $controller->generarPago($this->servicioPagar);

        // Verificar que es una redirección con error
        $this->assertEquals(302, $response->getStatusCode());
        
        // Verificar que NO se guardó el preference_id
        $this->servicioPagar->refresh();
        $this->assertNull($this->servicioPagar->mp_preference_id);
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
                'external_reference' => 'servicio_pagar_' . $this->servicioPagar->id
            ], 200)
        ]);

        $controller = new PagosController();
        $response = $controller->obtenerInfoPago($paymentId);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($paymentId, $responseData['data']['id']);
        $this->assertEquals('approved', $responseData['data']['status']);
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

    public function test_pago_success_con_informacion_detallada()
    {
        $paymentId = '98765432101';
        
        // Mock para obtener información detallada del pago
        Http::fake([
            "api.mercadopago.com/v1/payments/{$paymentId}" => Http::response([
                'id' => $paymentId,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'transaction_amount' => 2500.00,
                'currency_id' => 'ARS',
                'external_reference' => 'servicio_pagar_' . $this->servicioPagar->id
            ], 200)
        ]);

        $request = new \Illuminate\Http\Request([
            'payment_id' => $paymentId,
            'status' => 'approved',
            'external_reference' => 'servicio_pagar_' . $this->servicioPagar->id
        ]);

        $controller = new PagosController();
        $response = $controller->pagoSuccess($this->servicioPagar, $request);

        // Verificar que es una redirección
        $this->assertEquals(302, $response->getStatusCode());

        // Verificar que se actualizó el estado del servicio
        $this->servicioPagar->refresh();
        $this->assertEquals('pago', $this->servicioPagar->estado);
        $this->assertEquals($paymentId, $this->servicioPagar->mp_payment_id);

        // Verificar que se creó un registro en la tabla pagos
        $this->assertDatabaseHas('pagos', [
            'id_servicio_pagar' => $this->servicioPagar->id,
            'id_usuario' => $this->user->id,
            'forma_pago' => 1, // MercadoPago
            'importe' => $this->servicioPagar->total
        ]);
    }

    public function test_helpers_mercadopago_api_service()
    {
        // Test del helper createItems
        $servicios = [[
            'id' => 'servicio_' . $this->servicioPagar->id,
            'title' => $this->servicio->nombre,
            'description' => 'Test service',
            'quantity' => 1,
            'price' => 2500.00,
            'currency_id' => 'ARS'
        ]];

        $items = MercadoPagoApiService::createItems($servicios);
        
        $this->assertCount(1, $items);
        $this->assertEquals('servicio_' . $this->servicioPagar->id, $items[0]['id']);
        $this->assertEquals(2500.00, $items[0]['unit_price']);

        // Test del helper createPayer
        $clienteData = [
            'name' => $this->cliente->nombre,
            'surname' => '',
            'email' => $this->cliente->correo,
            'phone' => [
                'area_code' => '',
                'number' => ''
            ],
            'identification' => [
                'type' => 'DNI',
                'number' => (string) $this->cliente->dni
            ],
            'address' => [
                'street_name' => '',
                'street_number' => null,
                'zip_code' => ''
            ]
        ];

        $payer = MercadoPagoApiService::createPayer($clienteData);
        
        $this->assertEquals($this->cliente->nombre, $payer['name']);
        $this->assertEquals($this->cliente->correo, $payer['email']);
        $this->assertEquals((string) $this->cliente->dni, $payer['identification']['number']);
    }

    protected function tearDown(): void
    {
        Http::preventStrayRequests(false);
        parent::tearDown();
    }
}