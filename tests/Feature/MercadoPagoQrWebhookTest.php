<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\MercadoPagoPOS;
use App\Models\MercadoPagoQROrder;
use App\Models\MercadoPagoStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoQrWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private MercadoPagoQROrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa QR',
            'cuit' => '20999888777',
            'correo' => 'qr@test.com',
            'MP_ACCESS_TOKEN' => 'token-empresa-qr',
            'MP_USER_ID' => '777888999',
        ]);

        $store = MercadoPagoStore::create([
            'empresa_id' => $this->empresa->id,
            'external_id' => 'STORE-QR',
            'mp_store_id' => '1000',
            'name' => 'Sucursal QR',
        ]);

        $pos = MercadoPagoPOS::create([
            'mercadopago_store_id' => $store->id,
            'external_id' => 'POS-QR',
            'mp_pos_id' => '2000',
            'name' => 'Caja QR',
        ]);

        $this->order = MercadoPagoQROrder::create([
            'mercadopago_pos_id' => $pos->id,
            'external_reference' => 'QR-TEST-1',
            'total_amount' => 1000.00,
            'status' => 'pending',
        ]);
    }

    private function fakePago(string $tokenValido, float $monto = 1000.0): void
    {
        Http::fake(function (ClientRequest $request) use ($tokenValido, $monto) {
            if (str_contains($request->url(), '/v1/payments/')) {
                $authorization = $request->header('Authorization')[0] ?? '';

                if ($authorization !== 'Bearer '.$tokenValido) {
                    return Http::response(['message' => 'not found'], 404);
                }

                return Http::response([
                    'id' => '777',
                    'status' => 'approved',
                    'external_reference' => 'QR-TEST-1',
                    'transaction_amount' => $monto,
                ], 200);
            }

            return Http::response([], 200);
        });
    }

    public function test_webhook_qr_usa_el_token_de_la_empresa_notificada(): void
    {
        $this->fakePago('token-empresa-qr');

        $this->postJson(
            '/api/mercadopago/webhook/qr?empresa_id='.$this->empresa->id,
            [
                'action' => 'payment.created',
                'type' => 'payment',
                'data' => ['id' => '777'],
            ]
        )->assertStatus(200);

        $order = $this->order->fresh();

        $this->assertSame('paid', $order->status);
        $this->assertSame('777', $order->payment_id);
    }

    public function test_webhook_qr_procesa_sin_firma_y_busca_el_token_disponible(): void
    {
        $this->fakePago('token-empresa-qr');

        $this->postJson(
            '/api/mercadopago/webhook/qr',
            [
                'action' => 'payment.created',
                'data' => ['id' => '777'],
            ]
        )->assertStatus(200);

        $this->assertSame('paid', $this->order->fresh()->status);
    }

    public function test_webhook_qr_con_monto_distinto_no_marca_la_orden_como_pagada(): void
    {
        $this->fakePago('token-empresa-qr', 900.0);

        $this->postJson(
            '/api/mercadopago/webhook/qr?empresa_id='.$this->empresa->id,
            [
                'action' => 'payment.created',
                'data' => ['id' => '777'],
            ]
        )->assertStatus(200);

        $this->assertSame('pending', $this->order->fresh()->status);
    }

    public function test_webhook_qr_ignora_topicos_que_no_son_pagos(): void
    {
        Http::fake();

        $this->postJson('/api/mercadopago/webhook/qr', [
            'action' => 'order.created',
            'type' => 'order',
            'data' => ['id' => '777'],
        ])->assertStatus(200)->assertJson(['status' => 'ignored']);

        $this->assertSame('pending', $this->order->fresh()->status);
    }
}
