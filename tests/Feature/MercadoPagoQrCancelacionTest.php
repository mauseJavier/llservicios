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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoQrCancelacionTest extends TestCase
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
            'nombre' => 'Empresa QR Cancel',
            'cuit' => '20999888000',
            'correo' => 'qr-cancel@test.com',
            'MP_ACCESS_TOKEN' => 'token-empresa-qr',
            'MP_USER_ID' => '777888999',
        ]);

        $this->usuario = User::create([
            'name' => 'Usuario QR Cancel',
            'email' => 'qr-cancel-user@test.com',
            'password' => Hash::make('secret123'),
            'dni' => '30111333',
            'role_id' => $role->id,
            'empresa_id' => $this->empresa->id,
        ]);

        $store = MercadoPagoStore::create([
            'empresa_id' => $this->empresa->id,
            'external_id' => 'STORE-QR-CANCEL',
            'mp_store_id' => '3000',
            'name' => 'Sucursal QR Cancel',
        ]);

        $pos = MercadoPagoPOS::create([
            'mercadopago_store_id' => $store->id,
            'external_id' => 'POS-QR-CANCEL',
            'mp_pos_id' => '4000',
            'name' => 'Caja QR Cancel',
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Cliente QR Cancel',
            'correo' => 'cliente-qr-cancel@test.com',
            'telefono' => null,
            'dni' => '30111888',
        ]);

        $servicio = Servicio::create([
            'nombre' => 'Consulta',
            'descripcion' => 'Consulta general',
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
            'external_reference' => 'QR-TEST-CANCEL',
            'in_store_order_id' => 'INSTORE-CANCEL',
            'total_amount' => 100,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    private function fakeSinPagos(): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
        ]);
    }

    private function fakePagoAprobado(float $monto = 100): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response([
                'results' => [['id' => 555444333]],
            ], 200),
            'api.mercadopago.com/v1/payments/555444333' => Http::response([
                'id' => 555444333,
                'status' => 'approved',
                'external_reference' => 'QR-TEST-CANCEL',
                'transaction_amount' => $monto,
                'collector_id' => 777888999,
            ], 200),
            '*' => Http::response([], 200),
        ]);
    }

    public function test_cancela_localmente_cuando_mercadopago_no_tiene_la_orden(): void
    {
        $this->actingAs($this->usuario);

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
            'api.mercadopago.com/instore/qr/*' => Http::response([
                'error' => 'point_of_sale_in_store_order_not_found',
                'message' => 'The required PointOfSaleInStoreOrder not found',
                'status' => 404,
                'causes' => [],
            ], 404),
            'api.mercadopago.com/instore/orders/qr/*' => Http::response([
                'error' => 'in_store_order_delete_error',
                'message' => 'An error occurred when deleting the InStoreOrder',
                'status' => 400,
                'causes' => [],
            ], 400),
        ]);

        $this->postJson('/api/qr/cancel', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'cancelled',
            ]);

        $this->assertSame('cancelled', $this->orden->fresh()->status);
        Http::assertNotSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_no_cancela_si_el_pago_ya_fue_acreditado(): void
    {
        $this->actingAs($this->usuario);
        $this->fakePagoAprobado();

        $this->postJson('/api/qr/cancel', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'paid',
            ]);

        $this->assertSame('paid', $this->orden->fresh()->status);
        $this->assertSame('pago', $this->servicioPagar->fresh()->estado);
        Http::assertNotSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_no_elimina_el_qr_de_otro_cobro_en_la_misma_caja(): void
    {
        $this->actingAs($this->usuario);

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
            'api.mercadopago.com/instore/qr/*' => Http::response([
                'external_reference' => 'QR-OTRO-COBRO',
                'in_store_order_id' => 'INSTORE-OTRO',
                'status' => 'opened',
            ], 200),
            'api.mercadopago.com/instore/orders/qr/*' => Http::response([], 204),
        ]);

        $this->postJson('/api/qr/cancel', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'cancelled',
            ]);

        $this->assertSame('cancelled', $this->orden->fresh()->status);
        Http::assertNotSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_elimina_la_orden_en_mercadopago_cuando_coincide(): void
    {
        $this->actingAs($this->usuario);

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
            'api.mercadopago.com/instore/qr/*' => Http::response([
                'external_reference' => 'QR-TEST-CANCEL',
                'in_store_order_id' => 'INSTORE-CANCEL',
                'status' => 'opened',
            ], 200),
            'api.mercadopago.com/instore/orders/qr/*' => Http::response([], 204),
        ]);

        $this->postJson('/api/qr/cancel', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'cancelled',
                'upstream_cancelled' => true,
            ]);

        $this->assertSame('cancelled', $this->orden->fresh()->status);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), '/instore/orders/qr/seller/collectors/777888999/pos/POS-QR-CANCEL/qrs'));
    }

    public function test_cancelar_una_orden_ya_cancelada_es_idempotente(): void
    {
        $this->actingAs($this->usuario);
        $this->orden->update(['status' => 'cancelled']);

        Http::fake();

        $this->postJson('/api/qr/cancel', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'cancelled',
            ]);

        Http::assertNothingSent();
    }

    public function test_si_no_se_puede_consultar_la_orden_intenta_eliminar_la_ultima_pendiente(): void
    {
        $this->actingAs($this->usuario);

        Http::fake([
            'api.mercadopago.com/v1/payments/search*' => Http::response(['results' => []], 200),
            'api.mercadopago.com/instore/qr/*' => Http::response(['error' => 'internal_error'], 500),
            'api.mercadopago.com/instore/orders/qr/*' => Http::response([], 204),
        ]);

        $this->postJson('/api/qr/cancel', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'cancelled',
            ]);

        $this->assertSame('cancelled', $this->orden->fresh()->status);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_check_status_marca_expirada_una_orden_vencida(): void
    {
        $this->actingAs($this->usuario);
        $this->fakeSinPagos();
        $this->orden->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/qr/check-status', ['order_id' => $this->orden->id])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'expired',
            ]);

        $this->assertSame('expired', $this->orden->fresh()->status);
    }
}
