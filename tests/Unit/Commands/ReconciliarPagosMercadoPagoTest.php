<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FormaPago;
use App\Models\Pagos;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class ReconciliarPagosMercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    protected $empresa;
    protected $cliente;
    protected $servicio;
    protected $servicioImpago;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mercadopago.access_token', 'TEST-123456789-global-token');
        Config::set('services.mercadopago.sandbox', true);

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Reconcil',
            'cuit' => 12345678901,
            'correo' => 'reconcil@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-123456789-reconcil-token'
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'Cliente Reconcil',
            'dni' => 12345678,
            'telefono' => '987654321',
            'domicilio' => 'Test Address',
            'correo' => 'cliente-reconcil@test.com'
        ]);

        $this->servicio = Servicio::create([
            'nombre' => 'Servicio Reconcil',
            'descripcion' => 'Desc',
            'precio' => 1000.00,
            'empresa_id' => $this->empresa->id
        ]);

        $this->servicioImpago = ServicioPagar::create([
            'cliente_id' => $this->cliente->id,
            'servicio_id' => $this->servicio->id,
            'estado' => 'impago',
            'precio' => 1000.00,
            'cantidad' => 1
        ]);

        FormaPago::create(['nombre' => 'MercadoPago']);
    }

    public function test_recupera_pago_aprobado_no_procesado()
    {
        $paymentId = '666666666';

        Http::fake([
            'api.mercadopago.com/v1/payments/search*external_reference=servicio_pagar_*' => Http::response([
                'results' => [['id' => $paymentId, 'status' => 'approved']],
                'paging' => ['total' => 1, 'limit' => 5, 'offset' => 0],
            ], 200),

            'api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [],
                'paging' => ['total' => 0, 'limit' => 5, 'offset' => 0],
            ], 200),

            'api.mercadopago.com/v1/payments/' . $paymentId => Http::response([
                'id' => $paymentId,
                'status' => 'approved',
                'external_reference' => 'servicio_pagar_' . $this->servicioImpago->id,
                'transaction_amount' => 1000.0,
                'transaction_details' => ['net_received_amount' => 980.0],
            ], 200),
        ]);

        $this->artisan('mp:reconciliar-pagos')
            ->expectsOutputToContain('Reconciliación finalizada')
            ->assertExitCode(0);

        $this->assertEquals('pago', $this->servicioImpago->fresh()->estado);
        $this->assertEquals($paymentId, $this->servicioImpago->fresh()->mp_payment_id);

        $pago = Pagos::where('id_servicio_pagar', $this->servicioImpago->id)->first();
        $this->assertNotNull($pago);
    }

    public function test_no_hace_nada_cuando_no_hay_servicios_impagos()
    {
        $this->servicioImpago->update(['estado' => 'pago']);

        $this->artisan('mp:reconciliar-pagos')
            ->expectsOutputToContain('No hay servicios impagos')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }
}