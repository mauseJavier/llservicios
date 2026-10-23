<?php

namespace Tests\Unit\Services;

use App\Models\Empresa;
use App\Services\MercadoPago\MercadoPagoWebhookSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MercadoPagoWebhookSignatureServiceTest extends TestCase
{
    use RefreshDatabase;

    private MercadoPagoWebhookSignatureService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MercadoPagoWebhookSignatureService;
    }

    private function empresa(array $extra = []): Empresa
    {
        return Empresa::create(array_merge([
            'nombre' => 'Empresa Test',
            'cuit' => (string) random_int(20000000000, 29999999999),
            'correo' => 'empresa'.random_int(1, 99999).'@test.com',
            'MP_USER_ID' => '111222333',
            'MP_WEBHOOK_SECRET' => 'secreto-empresa',
        ], $extra));
    }

    private function requestConFirma(string $secret, string $dataId, string $requestId, int $ts, array $body = []): Request
    {
        $manifest = 'id:'.strtolower($dataId).';request-id:'.$requestId.';ts:'.$ts.';';
        $firma = 'ts='.$ts.',v1='.hash_hmac('sha256', $manifest, $secret);

        return Request::create(
            '/mercadopago/webhook?data.id='.$dataId,
            'POST',
            $body,
            [],
            [],
            [
                'HTTP_X_SIGNATURE' => $firma,
                'HTTP_X_REQUEST_ID' => $requestId,
            ]
        );
    }

    public function test_resuelve_empresa_por_empresa_id(): void
    {
        $empresa = $this->empresa();

        $request = Request::create('/mercadopago/webhook', 'POST', ['empresa_id' => $empresa->id]);

        $this->assertSame($empresa->id, $this->service->resolverEmpresa($request)?->id);
    }

    public function test_resuelve_empresa_por_user_id(): void
    {
        $empresa = $this->empresa(['MP_USER_ID' => '999888777']);

        $request = Request::create('/mercadopago/webhook', 'POST', ['user_id' => '999888777']);

        $this->assertSame($empresa->id, $this->service->resolverEmpresa($request)?->id);
    }

    public function test_firma_valida(): void
    {
        $empresa = $this->empresa();
        $request = $this->requestConFirma(
            'secreto-empresa',
            'ORD01ABC',
            'req-123',
            time(),
            ['empresa_id' => $empresa->id]
        );

        $resultado = $this->service->verificar($request);

        $this->assertSame(MercadoPagoWebhookSignatureService::ESTADO_VALIDA, $resultado['estado']);
        $this->assertSame($empresa->id, $resultado['empresa']?->id);
    }

    public function test_firma_invalida(): void
    {
        $empresa = $this->empresa();
        $request = $this->requestConFirma(
            'secreto-incorrecto',
            'ORD01ABC',
            'req-123',
            time(),
            ['empresa_id' => $empresa->id]
        );

        $resultado = $this->service->verificar($request);

        $this->assertSame(MercadoPagoWebhookSignatureService::ESTADO_INVALIDA, $resultado['estado']);
    }

    public function test_timestamp_fuera_de_tolerancia(): void
    {
        config(['services.mercadopago.webhook_tolerance' => 600]);

        $empresa = $this->empresa();
        $request = $this->requestConFirma(
            'secreto-empresa',
            'ORD01ABC',
            'req-123',
            time() - 3600,
            ['empresa_id' => $empresa->id]
        );

        $resultado = $this->service->verificar($request);

        $this->assertSame(MercadoPagoWebhookSignatureService::ESTADO_INVALIDA, $resultado['estado']);
    }

    public function test_empresa_sin_secret(): void
    {
        $empresa = $this->empresa(['MP_WEBHOOK_SECRET' => null]);
        $request = Request::create('/mercadopago/webhook', 'POST', ['empresa_id' => $empresa->id]);

        $resultado = $this->service->verificar($request);

        $this->assertSame(MercadoPagoWebhookSignatureService::ESTADO_SIN_SECRET, $resultado['estado']);
        $this->assertSame('empresa_sin_secret', $resultado['motivo']);
    }

    public function test_empresa_no_resuelta(): void
    {
        $request = Request::create('/mercadopago/webhook', 'POST', []);

        $resultado = $this->service->verificar($request);

        $this->assertSame(MercadoPagoWebhookSignatureService::ESTADO_SIN_SECRET, $resultado['estado']);
        $this->assertSame('empresa_no_resuelta', $resultado['motivo']);
        $this->assertNull($resultado['empresa']);
    }

    public function test_data_id_se_normaliza_a_minusculas(): void
    {
        $request = Request::create('/mercadopago/webhook?data.id=ORD01ABC', 'GET');

        $this->assertSame('ord01abc', $this->service->dataId($request));
    }
}
