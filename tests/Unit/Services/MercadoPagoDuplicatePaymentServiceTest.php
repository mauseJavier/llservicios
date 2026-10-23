<?php

namespace Tests\Unit\Services;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MercadoPagoReembolso;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Services\MercadoPago\MercadoPagoDuplicatePaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoDuplicatePaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Cliente $cliente;

    private Servicio $servicio;

    private ServicioPagar $servicioPagar;

    private MercadoPagoDuplicatePaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Duplicados',
            'cuit' => '20111111112',
            'correo' => 'duplicados@test.com',
            'MP_ACCESS_TOKEN' => 'token-empresa-duplicados',
            'MP_USER_ID' => '123456789',
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Javier Desmaret',
            'correo' => 'javier@test.com',
            'telefono' => '2942506803',
            'dni' => '35833716',
        ]);

        $this->servicio = Servicio::create([
            'nombre' => 'Rayos X',
            'descripcion' => 'Radiografía digital',
            'precio' => 450,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'cantidad' => 1,
            'precio' => 450,
            'estado' => 'impago',
            'periodo_servicio' => '2026-10-01',
            'fecha_vencimiento' => '2026-11-01',
        ]);

        $this->servicioPagar->created_at = now()->subDay();
        $this->servicioPagar->save();

        $this->service = app(MercadoPagoDuplicatePaymentService::class);
    }

    private function pago(string $id, float $monto, Carbon $fecha, array $extra = []): array
    {
        return array_merge([
            'id' => $id,
            'status' => 'approved',
            'external_reference' => 'servicio_pagar_'.$this->servicioPagar->id,
            'transaction_amount' => $monto,
            'currency_id' => 'ARS',
            'date_approved' => $fecha->toIso8601String(),
            'date_created' => $fecha->toIso8601String(),
            'transaction_amount_refunded' => 0,
            'payer' => ['email' => 'test_user@testuser.com'],
            'payment_method_id' => 'account_money',
        ], $extra);
    }

    private function fakeMercadoPago(array $pagos): void
    {
        Http::fake(function (ClientRequest $request) use ($pagos) {
            $url = $request->url();

            if (str_contains($url, '/v1/payments/search')) {
                return Http::response([
                    'results' => $pagos,
                    'paging' => ['total' => count($pagos)],
                ], 200);
            }

            if (str_contains($url, '/refunds')) {
                return Http::response([
                    'id' => 'refund-123',
                    'status' => 'approved',
                    'amount' => 450,
                ], 201);
            }

            if (preg_match('#/v1/payments/([^/?]+)$#', $url, $matches)) {
                $pago = collect($pagos)->firstWhere('id', $matches[1]);

                return $pago
                    ? Http::response($pago, 200)
                    : Http::response(['message' => 'not found'], 404);
            }

            return Http::response([], 200);
        });
    }

    public function test_detecta_duplicado_y_conserva_el_pago_mas_antiguo(): void
    {
        $primero = $this->pago('pago-viejo', 450, now()->subHours(2));
        $segundo = $this->pago('pago-nuevo', 450, now()->subHour());

        $this->fakeMercadoPago([$segundo, $primero]);

        $candidatos = $this->service->buscarDuplicados(
            $this->empresa,
            now()->subDays(2),
            now()
        );

        $this->assertCount(1, $candidatos);
        $this->assertSame('servicio_pagar_'.$this->servicioPagar->id, $candidatos[0]['referencia']);
        $this->assertSame('pago-viejo', $candidatos[0]['primary']['id']);
        $this->assertCount(1, $candidatos[0]['duplicates']);
        $this->assertSame('pago-nuevo', $candidatos[0]['duplicates'][0]['id']);
        $this->assertTrue($candidatos[0]['elegible']);
        $this->assertSame([], $candidatos[0]['motivos']);
    }

    public function test_ignora_pagos_historicos_anteriores_al_servicio(): void
    {
        $viejo1 = $this->pago('pago-historico-1', 100, now()->subDays(5));
        $viejo2 = $this->pago('pago-historico-2', 100, now()->subDays(4));

        $this->fakeMercadoPago([$viejo1, $viejo2]);

        $candidatos = $this->service->buscarDuplicados(
            $this->empresa,
            now()->subDays(7),
            now()
        );

        $this->assertCount(0, $candidatos);
    }

    public function test_no_es_elegible_si_los_montos_son_distintos(): void
    {
        $primero = $this->pago('pago-450', 450, now()->subHours(2));
        $segundo = $this->pago('pago-100', 100, now()->subHour());

        $this->fakeMercadoPago([$primero, $segundo]);

        $candidatos = $this->service->buscarDuplicados(
            $this->empresa,
            now()->subDays(2),
            now()
        );

        $this->assertCount(1, $candidatos);
        $this->assertFalse($candidatos[0]['elegible']);
        $this->assertContains('Monto o moneda distinta al pago principal', $candidatos[0]['motivos']);
    }

    public function test_reembolsa_pago_no_aplicado_y_registra_el_reembolso(): void
    {
        $primero = $this->pago('pago-viejo', 450, now()->subHours(2));
        $segundo = $this->pago('pago-nuevo', 450, now()->subHour());

        $this->fakeMercadoPago([$primero, $segundo]);

        $resultado = $this->service->reembolsar(
            $this->empresa,
            'pago-nuevo',
            'Pago duplicado'
        );

        $this->assertTrue($resultado['success']);
        $this->assertSame('refund-123', $resultado['refund_id']);

        $this->assertDatabaseHas('mp_reembolsos', [
            'payment_id' => 'pago-nuevo',
            'empresa_id' => $this->empresa->id,
            'estado' => 'aprobado',
            'mp_refund_id' => 'refund-123',
        ]);
    }

    public function test_no_reembolsa_un_pago_aplicado_a_un_servicio(): void
    {
        $pago = $this->pago('pago-aplicado', 450, now()->subHour());

        $this->servicioPagar->update([
            'estado' => 'pago',
            'mp_payment_id' => 'pago-aplicado',
        ]);

        $this->fakeMercadoPago([$pago]);

        $resultado = $this->service->reembolsar(
            $this->empresa,
            'pago-aplicado',
            'Pago duplicado'
        );

        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('aplicado', $resultado['mensaje']);
        $this->assertDatabaseCount('mp_reembolsos', 0);
    }

    public function test_no_reembolsa_un_pago_de_otra_empresa(): void
    {
        $otraEmpresa = Empresa::create([
            'nombre' => 'Otra Empresa',
            'cuit' => '20222222223',
            'correo' => 'otra@test.com',
            'MP_ACCESS_TOKEN' => 'token-otra-empresa',
        ]);

        $servicioOtraEmpresa = Servicio::create([
            'nombre' => 'Otro servicio',
            'descripcion' => 'Servicio de otra empresa',
            'precio' => 450,
            'empresa_id' => $otraEmpresa->id,
        ]);

        $servicioPagarOtraEmpresa = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $servicioOtraEmpresa->id,
            'cantidad' => 1,
            'precio' => 450,
            'estado' => 'impago',
            'periodo_servicio' => '2026-10-01',
            'fecha_vencimiento' => '2026-11-01',
        ]);

        $pago = $this->pago(
            'pago-otra-empresa',
            450,
            now()->subHour(),
            ['external_reference' => 'servicio_pagar_'.$servicioPagarOtraEmpresa->id]
        );

        $this->fakeMercadoPago([$pago]);

        $resultado = $this->service->reembolsar(
            $this->empresa,
            'pago-otra-empresa',
            'Intento cruzado'
        );

        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('no pertenece', $resultado['mensaje']);
        $this->assertDatabaseCount('mp_reembolsos', 0);
    }

    public function test_no_reembolsa_dos_veces_el_mismo_pago(): void
    {
        $pago = $this->pago('pago-unico', 450, now()->subHour());

        MercadoPagoReembolso::create([
            'payment_id' => 'pago-unico',
            'external_reference' => 'servicio_pagar_'.$this->servicioPagar->id,
            'empresa_id' => $this->empresa->id,
            'monto' => 450,
            'estado' => 'aprobado',
        ]);

        $this->fakeMercadoPago([$pago]);

        $resultado = $this->service->reembolsar(
            $this->empresa,
            'pago-unico',
            'Pago duplicado'
        );

        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('ya tiene un reembolso', $resultado['mensaje']);
    }
}
