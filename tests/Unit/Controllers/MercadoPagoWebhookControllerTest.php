<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use App\Http\Controllers\MercadoPago\MercadoPagoWebhookController;
use App\Jobs\ProcesarPagoJob;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FormaPago;
use App\Models\Pagos;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class MercadoPagoWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $empresa;
    protected $otraEmpresa;
    protected $cliente;
    protected $servicio;
    protected $servicioImpago;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa A',
            'cuit' => 12345678901,
            'correo' => 'a@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-empresa-a'
        ]);

        $this->otraEmpresa = Empresa::create([
            'nombre' => 'Empresa B',
            'cuit' => 22345678901,
            'correo' => 'b@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-empresa-b'
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Cliente Test',
            'dni' => 12345678,
            'telefono' => '987654321',
            'domicilio' => 'Test Address',
            'correo' => 'cliente@test.com'
        ]);

        $this->servicio = Servicio::create([
            'nombre' => 'Servicio A',
            'descripcion' => 'Desc',
            'precio' => 1000.00,
            'empresa_id' => $this->empresa->id
        ]);

        $servicioB = Servicio::create([
            'nombre' => 'Servicio B',
            'descripcion' => 'Desc',
            'precio' => 2000.00,
            'empresa_id' => $this->otraEmpresa->id
        ]);

        $this->servicioImpago = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 1000.00,
            'cantidad' => 1
        ]);

        ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $servicioB->id,
            'estado' => 'impago',
            'precio' => 2000.00,
            'cantidad' => 1
        ]);

        FormaPago::create(['nombre' => 'MercadoPago']);

        Config::set('services.mercadopago.access_token', 'TEST-123456789-global-token');
        Config::set('services.mercadopago.sandbox', true);

        $this->controller = new MercadoPagoWebhookController();
    }

    public function test_resuelve_referencia_individual()
    {
        $servicios = $this->invocarResolutor('servicio_pagar_' . $this->servicioImpago->id);

        $this->assertCount(1, $servicios);
        $this->assertEquals($this->servicioImpago->id, $servicios->first()->id);
    }

    public function test_resuelve_referencia_agrupada_solo_impagos_de_la_empresa()
    {
        // Marcar uno como pagado para verificar que no se incluye
        $servicioPagado = ServicioPagar::where('cliente_id', $this->cliente->id)
            ->whereHas('servicio', fn($q) => $q->where('empresa_id', $this->empresa->id))
            ->first();
        $servicioPagado->update(['estado' => 'pago']);

        $servicios = $this->invocarResolutor(
            'cliente_impagos_' . $this->cliente->id . '_' . $this->empresa->id
        );

        $this->assertTrue($servicios->isEmpty());
    }

    public function test_resuelve_referencia_agrupada_devuelve_impagos()
    {
        $servicios = $this->invocarResolutor(
            'cliente_impagos_' . $this->cliente->id . '_' . $this->empresa->id
        );

        $this->assertCount(1, $servicios);
        $this->assertEquals($this->servicioImpago->id, $servicios->first()->id);
    }

    public function test_referencia_desconocida_devuelve_vacio()
    {
        $servicios = $this->invocarResolutor('referencia-invalida');

        $this->assertTrue($servicios->isEmpty());
    }

    private function invocarResolutor(string $referencia)
    {
        $method = new \ReflectionMethod($this->controller, 'resolverServiciosPorReferencia');
        $method->setAccessible(true);

        return $method->invoke($this->controller, $referencia);
    }

    public function test_distribuye_neto_proporcional_con_redondeo_al_ultimo()
    {
        $s1 = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 1000.00,
            'cantidad' => 1
        ]);
        $s2 = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 3000.00,
            'cantidad' => 1
        ]);

        $importes = $this->invocarDistribuidor(collect([$s1, $s2]), 3800.0);

        $this->assertEquals(950.0, $importes[$s1->id]);
        $this->assertEquals(2850.0, $importes[$s2->id]);
        $this->assertEquals(3800.0, array_sum($importes));
    }

    public function test_distribuye_por_igual_cuando_subtotales_son_cero()
    {
        $s1 = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 0.00,
            'cantidad' => 1
        ]);
        $s2 = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 0.00,
            'cantidad' => 1
        ]);

        $importes = $this->invocarDistribuidor(collect([$s1, $s2]), 100.0);

        $this->assertEquals(50.0, $importes[$s1->id]);
        $this->assertEquals(50.0, $importes[$s2->id]);
        $this->assertEquals(100.0, array_sum($importes));
    }

    public function test_proceso_agregado_registra_pagos_proporcionales()
    {
        $s1 = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 1000.00,
            'cantidad' => 1
        ]);
        $s2 = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 3000.00,
            'cantidad' => 1
        ]);

        $payment = (object) [
            'id' => '100500',
            'status' => 'approved',
            'transaction_amount' => 4000.0,
            'transaction_details' => (object) ['net_received_amount' => 3800.0]
        ];

        $importes = $this->invocarDistribuidor(collect([$s1, $s2]), 3800.0);

        $this->invocarProcesador($s1, $payment, ['importe' => $importes[$s1->id]]);
        $this->invocarProcesador($s2, $payment, ['importe' => $importes[$s2->id]]);

        $this->assertEquals('pago', $s1->fresh()->estado);
        $this->assertEquals('pago', $s2->fresh()->estado);

        $pago1 = Pagos::where('id_servicio_pagar', $s1->id)->first();
        $pago2 = Pagos::where('id_servicio_pagar', $s2->id)->first();

        $this->assertNotNull($pago1);
        $this->assertNotNull($pago2);
        $this->assertEquals(950.0, (float) $pago1->importe);
        $this->assertEquals(2850.0, (float) $pago2->importe);
    }

    public function test_proceso_individual_mantiene_importe_neto()
    {
        $payment = (object) [
            'id' => '100501',
            'status' => 'approved',
            'transaction_amount' => 4000.0,
            'transaction_details' => (object) ['net_received_amount' => 3800.0]
        ];

        $this->invocarProcesador($this->servicioImpago, $payment);

        $this->assertEquals('pago', $this->servicioImpago->fresh()->estado);

        $pago = Pagos::where('id_servicio_pagar', $this->servicioImpago->id)->first();
        $this->assertNotNull($pago);
        $this->assertEquals(3800.0, (float) $pago->importe);
    }

    private function invocarDistribuidor($servicios, float $montoNeto)
    {
        $method = new \ReflectionMethod($this->controller, 'distribuirImportes');
        $method->setAccessible(true);

        return $method->invoke($this->controller, $servicios, $montoNeto);
    }

    private function invocarProcesador($servicioPagar, $payment, array $contexto = [])
    {
        $method = new \ReflectionMethod($this->controller, 'processPaymentNotification');
        $method->setAccessible(true);

        return $method->invoke($this->controller, $servicioPagar, $payment, $contexto);
    }

    public function test_pago_aprobado_despacha_notificacion_pago_realizado()
    {
        Queue::fake();

        $payment = (object) [
            'id' => '100600',
            'status' => 'approved',
            'transaction_amount' => 1000.0,
            'transaction_details' => (object) ['net_received_amount' => 980.0]
        ];

        $this->invocarProcesador($this->servicioImpago, $payment);

        Queue::assertPushed(ProcesarPagoJob::class);
    }

    public function test_pago_aprobado_no_repite_notificacion_si_ya_estaba_pagado()
    {
        Queue::fake();

        $this->servicioImpago->update(['estado' => 'pago', 'mp_payment_id' => '999']);

        $payment = (object) [
            'id' => '100601',
            'status' => 'approved',
            'transaction_amount' => 1000.0,
            'transaction_details' => (object) ['net_received_amount' => 980.0]
        ];

        $this->invocarProcesador($this->servicioImpago, $payment);

        Queue::assertNothingPushed();
    }

    public function test_pago_agrupado_despacha_una_notificacion_por_servicio()
    {
        Queue::fake();

        $s1 = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 1000.00,
            'cantidad' => 1
        ]);
        $s2 = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 3000.00,
            'cantidad' => 1
        ]);

        $payment = (object) [
            'id' => '100602',
            'status' => 'approved',
            'transaction_amount' => 4000.0,
            'transaction_details' => (object) ['net_received_amount' => 3800.0]
        ];

        $importes = $this->invocarDistribuidor(collect([$s1, $s2]), 3800.0);
        $this->invocarProcesador($s1, $payment, ['importe' => $importes[$s1->id]]);
        $this->invocarProcesador($s2, $payment, ['importe' => $importes[$s2->id]]);

        Queue::assertCount(2, ProcesarPagoJob::class);
    }

    public function test_extrae_pagos_aprobados_filtra_por_estado()
    {
        $payments = [
            ['id' => '111', 'status' => 'approved'],
            ['id' => '222', 'status' => 'pending'],
            ['id' => '333', 'status' => 'approved'],
            ['id' => '444', 'status' => 'cancelled'],
        ];

        $ids = $this->invocarExtractorPagos($payments);

        $this->assertEquals(['111', '333'], $ids);
    }

    public function test_extrae_pagos_aprobados_sin_aprobados_devuelve_vacio()
    {
        $payments = [
            ['id' => '222', 'status' => 'pending'],
            ['id' => '444', 'status' => 'cancelled'],
        ];

        $ids = $this->invocarExtractorPagos($payments);

        $this->assertEmpty($ids);
    }

    public function test_extrae_pagos_aprobados_acepta_objetos_del_sdk()
    {
        $pagoAprobado = new \MercadoPago\Resources\MerchantOrder\Payment();
        $pagoAprobado->id = 555;
        $pagoAprobado->status = 'approved';

        $pagoPendiente = new \MercadoPago\Resources\MerchantOrder\Payment();
        $pagoPendiente->id = 666;
        $pagoPendiente->status = 'pending';

        $ids = $this->invocarExtractorPagos([$pagoAprobado, $pagoPendiente]);

        $this->assertEquals(['555'], $ids);
    }

    private function invocarExtractorPagos(array $payments)
    {
        $method = new \ReflectionMethod($this->controller, 'extraerPagosAprobados');
        $method->setAccessible(true);

        return $method->invoke($this->controller, $payments);
    }

    public function test_resuelve_token_por_empresa_id()
    {
        $token = $this->invocarResolvedorToken([
            'empresa_id' => $this->empresa->id,
        ], collect());

        $this->assertEquals('TEST-123456789-empresa-a', $token);
    }

    public function test_resuelve_token_por_referencia_externa()
    {
        $servicios = collect([$this->servicioImpago]);

        $token = $this->invocarResolvedorToken([
            'external_reference' => 'servicio_pagar_' . $this->servicioImpago->id,
        ], $servicios);

        $this->assertEquals('TEST-123456789-empresa-a', $token);
    }

    public function test_resuelve_token_por_user_id()
    {
        $this->empresa->update(['MP_USER_ID' => '12345']);

        $token = $this->invocarResolvedorToken([
            'user_id' => '12345',
        ], collect());

        $this->assertEquals('TEST-123456789-empresa-a', $token);
    }

    public function test_resuelve_token_global_como_fallback()
    {
        $token = $this->invocarResolvedorToken([], collect());

        $this->assertEquals('TEST-123456789-global-token', $token);
    }

    public function test_respuesta_webhook_retry_devuelve_500()
    {
        $method = new \ReflectionMethod($this->controller, 'respuestaWebhook');
        $method->setAccessible(true);

        $respuesta = $method->invoke($this->controller, 'retry');

        $this->assertEquals(500, $respuesta->getStatusCode());
    }

    public function test_respuesta_webhook_procesado_devuelve_200()
    {
        $method = new \ReflectionMethod($this->controller, 'respuestaWebhook');
        $method->setAccessible(true);

        $respuesta = $method->invoke($this->controller, 'procesado');

        $this->assertEquals(200, $respuesta->getStatusCode());
    }

    public function test_webhook_payment_devuelve_500_si_no_puede_obtener_el_pago()
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response(['message' => 'not found'], 404),
        ]);

        $respuesta = $this->postJson('/mercadopago/webhook', [
            'type' => 'payment',
            'data' => ['id' => '999999999'],
        ]);

        $respuesta->assertStatus(500);
    }

    public function test_webhook_payment_sin_servicios_devuelve_200()
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/888888888' => Http::response([
                'id' => '888888888',
                'status' => 'approved',
                'external_reference' => 'referencia-desconocida',
                'transaction_amount' => 100.0,
                'transaction_details' => ['net_received_amount' => 98.0],
            ], 200),
        ]);

        $respuesta = $this->postJson('/mercadopago/webhook', [
            'type' => 'payment',
            'data' => ['id' => '888888888'],
        ]);

        $respuesta->assertStatus(200);
        $respuesta->assertJson(['status' => 'ok']);
    }

    public function test_webhook_payment_procesa_pago_con_token_de_empresa_por_empresa_id()
    {
        Queue::fake();

        $paymentId = '777777777';

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => $paymentId,
                'status' => 'approved',
                'external_reference' => 'servicio_pagar_' . $this->servicioImpago->id,
                'transaction_amount' => 1000.0,
                'transaction_details' => ['net_received_amount' => 980.0],
            ], 200),
        ]);

        $respuesta = $this->postJson('/mercadopago/webhook', [
            'type' => 'payment',
            'data' => ['id' => $paymentId],
            'empresa_id' => $this->empresa->id,
        ]);

        $respuesta->assertStatus(200);

        $this->assertEquals('pago', $this->servicioImpago->fresh()->estado);
        $this->assertEquals($paymentId, $this->servicioImpago->fresh()->mp_payment_id);

        $pago = Pagos::where('id_servicio_pagar', $this->servicioImpago->id)->first();
        $this->assertNotNull($pago);

        Queue::assertPushed(ProcesarPagoJob::class);
    }

    public function test_webhook_payment_sin_empresa_id_intenta_con_todas_las_empresas()
    {
        Queue::fake();

        // La empresa dueña del pago es otra distinta del token global y sin MP_USER_ID,
        // por lo que el webhook solo puede resolver el token probando cada empresa.
        $otraEmpresa = Empresa::create([
            'nombre' => 'Empresa Dueña',
            'cuit' => 32345678901,
            'correo' => 'duena@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-duena-token',
        ]);

        $servicioDuena = Servicio::create([
            'nombre' => 'Servicio Dueña',
            'descripcion' => 'Desc',
            'precio' => 500.00,
            'empresa_id' => $otraEmpresa->id,
        ]);

        $servicioImpagoDuena = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $servicioDuena->id,
            'estado' => 'impago',
            'precio' => 500.00,
            'cantidad' => 1,
        ]);

        $paymentId = '555555555';

        Http::fake([
            // Fallo con el token global
            'api.mercadopago.com/v1/payments/555555555' => Http::sequence()
                ->push(['message' => 'not found'], 404)   // token global
                ->push(['message' => 'not found'], 404)   // empresa-a
                ->push([
                    'id' => $paymentId,
                    'status' => 'approved',
                    'external_reference' => 'servicio_pagar_' . $servicioImpagoDuena->id,
                    'transaction_amount' => 500.0,
                    'transaction_details' => ['net_received_amount' => 490.0],
                ], 200),                                  // token duena
        ]);

        $respuesta = $this->postJson('/mercadopago/webhook', [
            'type' => 'payment',
            'data' => ['id' => $paymentId],
        ]);

        $respuesta->assertStatus(200);
        $this->assertEquals('pago', $servicioImpagoDuena->fresh()->estado);
    }

    private function invocarResolvedorToken(array $contexto, $serviciosPagar)
    {
        $method = new \ReflectionMethod($this->controller, 'resolverAccessToken');
        $method->setAccessible(true);

        return $method->invoke($this->controller, $contexto, $serviciosPagar);
    }
}