<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Services\MercadoPago\MercadoPagoApiService;
use App\Services\MercadoPago\MercadoPagoLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class MercadoPagoLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $empresa;
    protected $cliente;
    protected $servicio;
    protected $servicioPagar;
    protected $linkService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mercadopago.access_token', 'TEST-123456789-access-token');
        Config::set('services.mercadopago.sandbox', true);
        Config::set('app.url', 'https://test-app.com');
        Config::set('app.env', 'production');

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Test',
            'cuit' => 12345678901,
            'correo' => 'empresa@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-empresa-token'
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

        $this->linkService = new MercadoPagoLinkService(new MercadoPagoApiService());
    }

    public function test_link_individual_genera_checkout_url()
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-individual-1',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-individual-1',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-individual-1'
            ], 201)
        ]);

        $url = $this->linkService->linkIndividual($this->servicioPagar);

        $this->assertNotNull($url);
        $this->assertEquals('https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-individual-1', $url);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['external_reference'] === 'servicio_pagar_' . $this->servicioPagar->id
                && $body['items'][0]['title'] === 'Servicio Test'
                && $body['items'][0]['unit_price'] === 2500.0;
        });
    }

    public function test_link_individual_devuelve_null_sin_token_empresa()
    {
        $this->empresa->update(['MP_ACCESS_TOKEN' => null]);

        $url = $this->linkService->linkIndividual($this->servicioPagar->refresh());

        $this->assertNull($url);
    }

    public function test_link_individual_devuelve_null_si_api_falla()
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'message' => 'Invalid access token',
                'error' => 'unauthorized'
            ], 401)
        ]);

        $url = $this->linkService->linkIndividual($this->servicioPagar);

        $this->assertNull($url);
    }

    public function test_link_cliente_impagos_genera_preferencia_agrupada()
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-agrupada-1',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-agrupada-1',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-agrupada-1'
            ], 201)
        ]);

        $servicios = [$this->servicioPagar];

        $url = $this->linkService->linkClienteImpagos(
            $this->cliente->id,
            $this->empresa->id,
            $servicios
        );

        $this->assertNotNull($url);
        $this->assertEquals('https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-agrupada-1', $url);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['external_reference'] === 'cliente_impagos_' . $this->cliente->id . '_' . $this->empresa->id
                && count($body['items']) === 1
                && $body['payer']['email'] === 'cliente@test.com';
        });
    }

    public function test_link_cliente_impagos_devuelve_null_sin_token()
    {
        $this->empresa->update(['MP_ACCESS_TOKEN' => null]);

        $url = $this->linkService->linkClienteImpagos(
            $this->cliente->id,
            $this->empresa->id,
            [$this->servicioPagar]
        );

        $this->assertNull($url);
    }

    public function test_items_desde_servicios_pagar_acepta_filas_stdclass()
    {
        $fila = (object) [
            'nombreServicio' => 'Servicio Desde Query',
            'cantidad' => 2,
            'precio' => 100.50
        ];

        $items = MercadoPagoApiService::itemsDesdeServiciosPagar([$fila]);

        $this->assertCount(1, $items);
        $this->assertEquals('Servicio Desde Query', $items[0]['title']);
        $this->assertEquals(2, $items[0]['quantity']);
        $this->assertEquals(100.50, $items[0]['unit_price']);
        $this->assertEquals('ARS', $items[0]['currency_id']);
    }
}