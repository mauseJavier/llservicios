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
}