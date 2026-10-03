<?php

namespace Tests\Unit\Commands;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Pagos;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditarPagosCruzadosMercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    protected $empresaA;

    protected $empresaB;

    protected $cliente;

    protected $servicioA;

    protected $servicioB;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mercadopago.access_token', 'TEST-123456789-global-token');
        Config::set('services.mercadopago.sandbox', true);

        $this->empresaA = Empresa::create([
            'nombre' => 'Empresa A',
            'cuit' => 12345678901,
            'correo' => 'a@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-empresa-a',
        ]);

        $this->empresaB = Empresa::create([
            'nombre' => 'Empresa B',
            'cuit' => 22345678901,
            'correo' => 'b@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-empresa-b',
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Cliente Auditoria',
            'dni' => 12345678,
            'telefono' => '987654321',
            'domicilio' => 'Test Address',
            'correo' => 'cliente@test.com',
        ]);

        $this->servicioA = Servicio::create([
            'nombre' => 'Servicio A',
            'descripcion' => 'Desc',
            'precio' => 1000.00,
            'empresa_id' => $this->empresaA->id,
        ]);

        $this->servicioB = Servicio::create([
            'nombre' => 'Servicio B',
            'descripcion' => 'Desc',
            'precio' => 2000.00,
            'empresa_id' => $this->empresaB->id,
        ]);
    }

    private function crearDeudaCruzada(int $empresaId, string $paymentId, string $periodo, string $fechaPago): ServicioPagar
    {
        $servicioId = $empresaId === $this->empresaA->id ? $this->servicioA->id : $this->servicioB->id;

        $servicioPagar = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $servicioId,
            'estado' => 'pago',
            'precio' => 1000.00,
            'cantidad' => 1,
            'mp_payment_id' => $paymentId,
            'periodo_servicio' => $periodo.'-01',
        ]);

        Pagos::create([
            'id_servicio_pagar' => $servicioPagar->id,
            'id_usuario' => 0,
            'forma_pago' => 1,
            'importe' => 1000.00,
            'mp_payment_id' => $paymentId,
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/'.$paymentId => Http::response([
                'id' => $paymentId,
                'status' => 'approved',
                'date_approved' => $fechaPago,
                'external_reference' => 'lote_test',
                'transaction_amount' => 1000.0,
            ], 200),
        ]);

        return $servicioPagar;
    }

    public function test_sin_empresa_devuelve_error()
    {
        $this->artisan('mp:auditar-pagos-cruzados')
            ->expectsOutputToContain('Indicá --empresa')
            ->assertExitCode(1);
    }

    public function test_empresa_inexistente_devuelve_error()
    {
        $this->artisan('mp:auditar-pagos-cruzados', ['--empresa' => '99999'])
            ->expectsOutputToContain('No existe la empresa')
            ->assertExitCode(1);
    }

    public function test_empresa_valor_invalido_devuelve_error()
    {
        $this->artisan('mp:auditar-pagos-cruzados', ['--empresa' => 'foo'])
            ->expectsOutputToContain('inválido')
            ->assertExitCode(1);
    }

    public function test_empresa_especifica_no_detecta_anomalias_de_otra_empresa()
    {
        $this->crearDeudaCruzada($this->empresaA->id, '111111', '2026-09', '2026-07-15');
        $this->crearDeudaCruzada($this->empresaB->id, '222222', '2026-09', '2026-07-15');

        $this->artisan('mp:auditar-pagos-cruzados', ['--empresa' => (string) $this->empresaA->id])
            ->expectsOutputToContain('Se detectaron 1')
            ->assertExitCode(0);
    }

    public function test_empresa_all_detecta_anomalias_de_todas()
    {
        $this->crearDeudaCruzada($this->empresaA->id, '111111', '2026-09', '2026-07-15');
        $this->crearDeudaCruzada($this->empresaB->id, '222222', '2026-09', '2026-07-15');

        $this->artisan('mp:auditar-pagos-cruzados', ['--empresa' => 'all'])
            ->expectsOutputToContain('Se detectaron 2')
            ->assertExitCode(0);
    }

    public function test_empresa_todas_es_alias_de_all()
    {
        $this->crearDeudaCruzada($this->empresaA->id, '111111', '2026-09', '2026-07-15');

        $this->artisan('mp:auditar-pagos-cruzados', ['--empresa' => 'todas'])
            ->expectsOutputToContain('Se detectaron 1')
            ->assertExitCode(0);
    }

    public function test_apply_revierte_la_deuda()
    {
        $servicioPagar = $this->crearDeudaCruzada($this->empresaA->id, '111111', '2026-09', '2026-07-15');

        $this->artisan('mp:auditar-pagos-cruzados', ['--empresa' => 'all', '--apply' => true])
            ->expectsConfirmation('¿Revertir estas deudas a impago y eliminar su pago asociado (todas las empresas)?', 'yes')
            ->assertExitCode(0);

        $this->assertEquals('impago', $servicioPagar->fresh()->estado);
        $this->assertNull($servicioPagar->fresh()->mp_payment_id);
        $this->assertNull(Pagos::where('id_servicio_pagar', $servicioPagar->id)->first());
    }

    public function test_pago_posterior_al_periodo_no_es_anomalia()
    {
        $this->crearDeudaCruzada($this->empresaA->id, '111111', '2026-07', '2026-08-15');

        $this->artisan('mp:auditar-pagos-cruzados', ['--empresa' => 'all'])
            ->expectsOutputToContain('No se detectaron pagos cruzados')
            ->assertExitCode(0);
    }
}
