<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\MercadoPagoPOS;
use App\Models\MercadoPagoQROrder;
use App\Models\MercadoPagoStore;
use App\Models\role;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoQrReconciliacionTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $usuario;

    private ServicioPagar $servicioPagar;

    private MercadoPagoQROrder $orden;

    protected function setUp(): void
    {
        parent::setUp();

        $role = role::create(['id' => 3, 'nombre' => 'Super']);

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa QR Recon',
            'cuit' => '20999888776',
            'correo' => 'qr-recon@test.com',
            'MP_ACCESS_TOKEN' => 'token-empresa-qr',
            'MP_USER_ID' => '777888999',
        ]);

        $this->usuario = User::create([
            'name' => 'Usuario QR Recon',
            'email' => 'qr-recon-user@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '30111222',
            'role_id' => $role->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $store = MercadoPagoStore::create([
            'empresa_id' => $this->empresa->id,
            'external_id' => 'STORE-QR-RECON',
            'mp_store_id' => '1000',
            'name' => 'Sucursal QR',
        ]);

        $pos = MercadoPagoPOS::create([
            'mercadopago_store_id' => $store->id,
            'external_id' => 'POS-QR-RECON',
            'mp_pos_id' => '2000',
            'name' => 'Caja QR',
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Cliente QR',
            'correo' => 'cliente-qr@test.com',
            'telefono' => null,
            'dni' => '30111999',
        ]);

        $servicio = Servicio::create([
            'nombre' => 'Análisis',
            'descripcion' => 'Análisis clínico',
            'precio' => 100,
            'tiempo' => 'mes',
            'empresa_id' => $this->empresa->id,
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'cantidad' => 1,
            'precio' => 100,
            'estado' => 'impago',
        ]);

        $this->orden = MercadoPagoQROrder::create([
            'mercadopago_pos_id' => $pos->id,
            'servicio_pagar_id' => $this->servicioPagar->id,
            'external_reference' => 'QR-TEST-RECON',
            'in_store_order_id' => 'INSTORE-1',
            'total_amount' => 100,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    private function fakePagoAprobado(float $monto = 100): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [['id' => 130632256941]],
            ], 200),
            'api.mercadopago.com/v1/payments/130632256941' => Http::response([
                'id' => 130632256941,
                'status' => 'approved',
                'external_reference' => 'QR-TEST-RECON',
                'transaction_amount' => $monto,
                'collector_id' => 777888999,
            ], 200),
            '*' => Http::response([], 200),
        ]);
    }

    public function test_check_status_confirma_pago_si_el_webhook_no_llego(): void
    {
        $this->actingAs($this->usuario);
        $this->fakePagoAprobado();

        $this->postJson('/api/qr/check-status', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'paid',
            ]);

        $this->assertSame('paid', $this->orden->fresh()->status);
        $this->assertSame('pago', $this->servicioPagar->fresh()->estado);
        $this->assertSame('130632256941', (string) $this->servicioPagar->fresh()->mp_payment_id);
        $this->assertDatabaseHas('pagos', [
            'id_servicio_pagar' => $this->servicioPagar->id,
            'importe' => 100,
        ]);
    }

    public function test_check_status_no_confirma_si_el_monto_no_coincide(): void
    {
        $this->actingAs($this->usuario);
        $this->fakePagoAprobado(90);

        $this->postJson('/api/qr/check-status', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'pending',
            ]);

        $this->assertSame('pending', $this->orden->fresh()->status);
        $this->assertSame('impago', $this->servicioPagar->fresh()->estado);
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_si_el_servicio_ya_esta_pago_no_genera_otro_qr(): void
    {
        $this->actingAs($this->usuario);
        $this->servicioPagar->update(['estado' => 'pago']);

        Http::fake();

        $this->postJson('/api/qr/create', [
            'servicioPagar' => $this->servicioPagar->id,
            'importe' => 100,
            'posMpId' => $this->orden->mercadopago_pos_id,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'already_paid' => true,
            ]);

        Http::assertNothingSent();
        // Solo la orden creada en el setUp: no se agregó otra.
        $this->assertSame(1, MercadoPagoQROrder::count());
    }

    public function test_no_permite_crear_orden_para_un_servicio_de_otra_empresa(): void
    {
        $otraEmpresa = Empresa::create([
            'nombre' => 'Otra Empresa',
            'cuit' => '20999888775',
            'correo' => 'otra@test.com',
        ]);

        $otroServicio = Servicio::create([
            'nombre' => 'Servicio Ajeno',
            'descripcion' => 'Ajeno',
            'precio' => 100,
            'tiempo' => 'mes',
            'empresa_id' => $otraEmpresa->id,
        ]);

        $servicioPagarAjeno = ServicioPagar::create([
            'cliente_id' => $this->servicioPagar->cliente_id,
            'servicio_id' => $otroServicio->id,
            'cantidad' => 1,
            'precio' => 100,
            'estado' => 'impago',
        ]);

        $this->actingAs($this->usuario);

        Http::fake();

        $this->postJson('/api/qr/create', [
            'servicioPagar' => $servicioPagarAjeno->id,
            'importe' => 100,
            'posMpId' => $this->orden->mercadopago_pos_id,
        ])->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_crea_orden_qr_con_el_importe_ajustado_en_caja(): void
    {
        $this->actingAs($this->usuario);

        Http::fake([
            'api.mercadopago.com/instore/orders/qr/*' => Http::response([
                'qr_data' => '00020101021243650016COM.MERCADOLIBRE',
                'in_store_order_id' => 'INSTORE-DESC',
            ], 200),
            '*' => Http::response([], 200),
        ]);

        $this->postJson('/api/qr/create', [
            'servicioPagar' => $this->servicioPagar->id,
            'importe' => 80,
            'posMpId' => $this->orden->mercadopago_pos_id,
        ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // El importe ajustado (descuento/incremento) es el que se envía a MercadoPago.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/instore/orders/qr/')
                && (float) $request['total_amount'] === 80.0
                && (float) $request['items'][0]['unit_price'] === 80.0;
        });

        $orden = MercadoPagoQROrder::latest('id')->first();
        $this->assertSame(80.0, (float) $orden->total_amount);
    }

    public function test_no_crea_orden_qr_con_importe_cero(): void
    {
        $this->actingAs($this->usuario);

        Http::fake();

        $this->postJson('/api/qr/create', [
            'servicioPagar' => $this->servicioPagar->id,
            'importe' => 0,
            'posMpId' => $this->orden->mercadopago_pos_id,
        ])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_base_url_usa_el_dominio_en_produccion(): void
    {
        config([
            'services.mercadopago.test_mode' => false,
            'services.mercadopago.public_url' => 'https://dev.ngrok-free.dev',
            'app.url' => 'https://midominio.com',
        ]);

        $this->assertSame('https://midominio.com', MercadoPagoApiService::getBaseUrl());
    }

    public function test_base_url_usa_ngrok_en_desarrollo(): void
    {
        config([
            'services.mercadopago.test_mode' => true,
            'services.mercadopago.public_url' => 'https://dev.ngrok-free.dev/',
            'app.url' => 'http://localhost:1234',
        ]);

        $this->assertSame('https://dev.ngrok-free.dev', MercadoPagoApiService::getBaseUrl());
    }
}
