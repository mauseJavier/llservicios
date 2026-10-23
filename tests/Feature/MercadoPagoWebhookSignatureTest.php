<?php

namespace Tests\Feature;

use App\Jobs\ProcesarPagoJob;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FormaPago;
use App\Models\MercadoPagoPagoProcesado;
use App\Models\Pagos;
use App\Models\Servicio;
use App\Models\ServicioPagar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MercadoPagoWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private ServicioPagar $servicioPagar;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Webhook',
            'cuit' => '20123456789',
            'correo' => 'webhook@test.com',
            'MP_ACCESS_TOKEN' => 'TEST-token-empresa',
            'MP_USER_ID' => '555444333',
            'MP_WEBHOOK_SECRET' => 'secreto-webhook',
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Cliente Webhook',
            'dni' => 30111222,
            'telefono' => '2942506803',
            'domicilio' => 'Calle Test 123',
            'correo' => 'cliente.webhook@test.com',
        ]);

        $servicio = Servicio::create([
            'nombre' => 'Servicio Webhook',
            'descripcion' => 'Desc',
            'precio' => 1000.00,
            'empresa_id' => $this->empresa->id,
        ]);

        $this->servicioPagar = ServicioPagar::create([
            'cliente_id' => $cliente->id,
            'servicio_id' => $servicio->id,
            'estado' => 'impago',
            'precio' => 1000.00,
            'cantidad' => 1,
        ]);

        FormaPago::create(['nombre' => 'MercadoPago']);
    }

    private function firmar(string $secret, string $dataId, string $requestId, int $ts): string
    {
        $manifest = 'id:'.strtolower($dataId).';request-id:'.$requestId.';ts:'.$ts.';';

        return 'ts='.$ts.',v1='.hash_hmac('sha256', $manifest, $secret);
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    private function notificacionAprobada(string $paymentId): array
    {
        $url = '/mercadopago/webhook?empresa_id='.$this->empresa->id.'&data.id='.$paymentId.'&type=payment';

        $headers = [
            'x-signature' => $this->firmar('secreto-webhook', $paymentId, 'req-'.$paymentId, time()),
            'x-request-id' => 'req-'.$paymentId,
        ];

        return [$url, $headers];
    }

    private function fakePagoAprobado(string $paymentId): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => $paymentId,
                'status' => 'approved',
                'external_reference' => 'servicio_pagar_'.$this->servicioPagar->id,
                'transaction_amount' => 1000.0,
                'transaction_details' => ['net_received_amount' => 950.0],
            ], 200),
        ]);
    }

    private function payload(string $paymentId): array
    {
        return [
            'type' => 'payment',
            'action' => 'payment.created',
            'data' => ['id' => $paymentId],
            'user_id' => $this->empresa->MP_USER_ID,
        ];
    }

    public function test_firma_valida_procesa_el_pago(): void
    {
        config(['services.mercadopago.webhook_signature_enforce' => true]);

        $paymentId = '100000001';
        $this->fakePagoAprobado($paymentId);
        [$url, $headers] = $this->notificacionAprobada($paymentId);

        $this->postJson($url, $this->payload($paymentId), $headers)
            ->assertStatus(200);

        $this->assertSame('pago', $this->servicioPagar->fresh()->estado);
        $this->assertDatabaseHas('mp_pagos_procesados', ['payment_id' => $paymentId]);
        $this->assertNotNull(Pagos::where('id_servicio_pagar', $this->servicioPagar->id)->first());
    }

    public function test_firma_invalida_devuelve_401_y_no_procesa(): void
    {
        config(['services.mercadopago.webhook_signature_enforce' => true]);

        $paymentId = '100000002';
        $this->fakePagoAprobado($paymentId);

        $url = '/mercadopago/webhook?empresa_id='.$this->empresa->id.'&data.id='.$paymentId.'&type=payment';
        $headers = [
            'x-signature' => $this->firmar('secreto-incorrecto', $paymentId, 'req-x', time()),
            'x-request-id' => 'req-x',
        ];

        $this->postJson($url, $this->payload($paymentId), $headers)
            ->assertStatus(401);

        $this->assertSame('impago', $this->servicioPagar->fresh()->estado);
        $this->assertNull(MercadoPagoPagoProcesado::where('payment_id', $paymentId)->first());
    }

    public function test_sin_firma_con_enforce_devuelve_401(): void
    {
        config(['services.mercadopago.webhook_signature_enforce' => true]);

        $paymentId = '100000003';
        $url = '/mercadopago/webhook?empresa_id='.$this->empresa->id.'&data.id='.$paymentId.'&type=payment';

        $this->postJson($url, $this->payload($paymentId))
            ->assertStatus(401);

        $this->assertSame('impago', $this->servicioPagar->fresh()->estado);
    }

    public function test_empresa_sin_secret_con_enforce_devuelve_401(): void
    {
        config(['services.mercadopago.webhook_signature_enforce' => true]);

        $this->empresa->update(['MP_WEBHOOK_SECRET' => null]);

        $paymentId = '100000004';
        $url = '/mercadopago/webhook?empresa_id='.$this->empresa->id.'&data.id='.$paymentId.'&type=payment';

        $this->postJson($url, $this->payload($paymentId))
            ->assertStatus(401);
    }

    public function test_sin_enforce_procesa_aunque_la_firma_sea_invalida(): void
    {
        config(['services.mercadopago.webhook_signature_enforce' => false]);

        $paymentId = '100000005';
        $this->fakePagoAprobado($paymentId);

        $url = '/mercadopago/webhook?empresa_id='.$this->empresa->id.'&data.id='.$paymentId.'&type=payment';
        $headers = [
            'x-signature' => 'ts='.time().',v1=firma-invalida',
            'x-request-id' => 'req-y',
        ];

        $this->postJson($url, $this->payload($paymentId), $headers)
            ->assertStatus(200);

        $this->assertSame('pago', $this->servicioPagar->fresh()->estado);
    }

    public function test_timestamp_vencido_devuelve_401(): void
    {
        config([
            'services.mercadopago.webhook_signature_enforce' => true,
            'services.mercadopago.webhook_tolerance' => 600,
        ]);

        $paymentId = '100000006';
        $url = '/mercadopago/webhook?empresa_id='.$this->empresa->id.'&data.id='.$paymentId.'&type=payment';
        $headers = [
            'x-signature' => $this->firmar('secreto-webhook', $paymentId, 'req-z', time() - 7200),
            'x-request-id' => 'req-z',
        ];

        $this->postJson($url, $this->payload($paymentId), $headers)
            ->assertStatus(401);
    }

    public function test_pago_rechazado_no_desmarca_un_servicio_ya_pagado(): void
    {
        $this->servicioPagar->update(['estado' => 'pago']);

        $paymentId = '100000007';
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'id' => $paymentId,
                'status' => 'rejected',
                'external_reference' => 'servicio_pagar_'.$this->servicioPagar->id,
                'transaction_amount' => 1000.0,
            ], 200),
        ]);

        $url = '/mercadopago/webhook?empresa_id='.$this->empresa->id.'&data.id='.$paymentId.'&type=payment';

        $this->postJson($url, $this->payload($paymentId))
            ->assertStatus(200);

        $this->assertSame('pago', $this->servicioPagar->fresh()->estado);
    }

    public function test_notificacion_duplicada_no_duplica_el_pago(): void
    {
        config(['services.mercadopago.webhook_signature_enforce' => true]);

        $paymentId = '100000008';
        $this->fakePagoAprobado($paymentId);
        [$url, $headers] = $this->notificacionAprobada($paymentId);

        $this->postJson($url, $this->payload($paymentId), $headers)->assertStatus(200);
        $this->postJson($url, $this->payload($paymentId), $headers)->assertStatus(200);

        $this->assertSame(1, Pagos::where('id_servicio_pagar', $this->servicioPagar->id)->count());
        $this->assertSame(1, MercadoPagoPagoProcesado::where('payment_id', $paymentId)->count());
        Queue::assertPushed(ProcesarPagoJob::class, 1);
    }
}
