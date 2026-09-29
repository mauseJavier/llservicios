<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Services\MercadoPago\MercadoPagoLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class EnlacePagoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $empresa;
    protected $cliente;
    protected $servicioPagar;

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

        $servicio = Servicio::create([
            'nombre' => 'Servicio Test',
            'descripcion' => 'Descripción del servicio de prueba',
            'precio' => 2500.00,
            'empresa_id' => $this->empresa->id
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $servicio->id,
            'estado' => 'impago',
            'precio' => 2500.00,
            'cantidad' => 1
        ]);
    }

    private function fakeCheckoutPreference(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-enlace-1',
                'init_point' => 'https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-enlace-1',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-enlace-1'
            ], 201)
        ]);
    }

    public function test_individual_impago_redirige_al_checkout()
    {
        $this->fakeCheckoutPreference();

        $url = MercadoPagoLinkService::urlEnlaceIndividual($this->servicioPagar->id);

        $this->get($url)
            ->assertRedirect('https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-enlace-1');
    }

    public function test_individual_pagado_muestra_vista_pagado()
    {
        $this->servicioPagar->update(['estado' => 'pago']);

        $url = MercadoPagoLinkService::urlEnlaceIndividual($this->servicioPagar->id);

        $this->get($url)
            ->assertOk()
            ->assertViewIs('mercadopago.pagado');

        Http::assertNothingSent();
    }

    public function test_cliente_con_impagos_redirige_al_checkout()
    {
        $this->fakeCheckoutPreference();

        $url = MercadoPagoLinkService::urlEnlaceCliente($this->cliente->id, $this->empresa->id);

        $this->get($url)
            ->assertRedirect('https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=pref-enlace-1');
    }

    public function test_cliente_sin_impagos_muestra_vista_pagado()
    {
        $this->servicioPagar->update(['estado' => 'pago']);

        $url = MercadoPagoLinkService::urlEnlaceCliente($this->cliente->id, $this->empresa->id);

        $this->get($url)
            ->assertOk()
            ->assertViewIs('mercadopago.pagado');

        Http::assertNothingSent();
    }

    public function test_firma_invalida_devuelve_403()
    {
        $this->get('/pago/enlace/individual/' . $this->servicioPagar->id)
            ->assertForbidden();
    }
}