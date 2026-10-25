<?php

namespace Tests\Unit\Services;

use App\Services\MercadoPago\MercadoPagoQRService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoQRServiceTest extends TestCase
{
    private MercadoPagoQRService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new MercadoPagoQRService;
        $this->service->setAccessToken('TEST-token');
        $this->service->setUserId('123456');
    }

    public function test_consulta_orden_usa_la_ruta_correcta(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response(['id' => 'ORD01'], 200),
        ]);

        $resultado = $this->service->getQROrder('POS-001');

        $this->assertTrue($resultado['success']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/instore/qr/seller/collectors/123456/pos/POS-001/orders');
    }

    public function test_cancelar_orden_usa_la_ruta_correcta(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([], 204),
        ]);

        $resultado = $this->service->deleteQROrder('POS-001');

        $this->assertTrue($resultado['success']);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->url() === 'https://api.mercadopago.com/instore/orders/qr/seller/collectors/123456/pos/POS-001/qrs');
    }

    public function test_cancelar_orden_sin_orden_activa_se_marca_already_gone(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'error' => 'in_store_order_delete_error',
                'message' => 'An error occurred when deleting the InStoreOrder',
                'status' => 400,
                'causes' => [],
            ], 400),
        ]);

        $resultado = $this->service->deleteQROrder('POS-001');

        $this->assertFalse($resultado['success']);
        $this->assertTrue($resultado['already_gone']);
        $this->assertSame('in_store_order_delete_error', $resultado['error_code']);
        $this->assertSame(400, $resultado['status_code']);
    }

    public function test_consultar_orden_sin_orden_activa_se_marca_not_found(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'error' => 'point_of_sale_in_store_order_not_found',
                'message' => 'The required PointOfSaleInStoreOrder not found',
                'status' => 404,
                'causes' => [],
            ], 404),
        ]);

        $resultado = $this->service->getQROrder('POS-001');

        $this->assertFalse($resultado['success']);
        $this->assertTrue($resultado['not_found']);
        $this->assertSame(404, $resultado['status_code']);
    }

    public function test_crear_orden_usa_la_ruta_legacy_correcta(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'qr_data' => '0002010102',
                'in_store_order_id' => 'ORD01',
            ], 201),
        ]);

        $resultado = $this->service->createQROrder('POS-001', [
            'external_reference' => 'QR-TEST-1',
            'title' => 'Servicio',
            'total_amount' => 1000.0,
        ]);

        $this->assertTrue($resultado['success']);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://api.mercadopago.com/instore/orders/qr/seller/collectors/123456/pos/POS-001/qrs');
    }
}
