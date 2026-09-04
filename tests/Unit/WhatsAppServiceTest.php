<?php

namespace Tests\Unit;

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WhatsAppServiceTest extends TestCase
{
    protected $whatsappService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configurar variables de entorno de prueba
        config([
            'services.whatsapp.api_url' => 'https://api-test.whatsapp.com',
            'services.whatsapp.api_key' => 'test_api_key',
            'services.whatsapp.instance_id' => 'test-instance-id'
        ]);

        $this->whatsappService = new WhatsAppService();
    }

    /** @test */
    public function puede_enviar_mensaje_de_texto()
    {
        // Mock de la respuesta HTTP
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'test123'
            ], 200)
        ]);

        $resultado = $this->whatsappService->sendTextMessage(
            '5492942506803',
            'Mensaje de prueba'
        );

        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('data', $resultado);
    }

    /** @test */
    public function puede_enviar_mensaje_con_botones()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'btn123'
            ], 200)
        ]);

        $resultado = $this->whatsappService->sendButtons(
            '5492942506803',
            'Título de prueba',
            'Descripción del mensaje',
            'Footer del mensaje',
            [
                ['type' => 'reply', 'displayText' => 'Opción 1', 'id' => 'opt1'],
                ['type' => 'reply', 'displayText' => 'Opción 2', 'id' => 'opt2'],
                ['type' => 'reply', 'displayText' => 'Información recibida', 'id' => 'info_recibida'],
            ]
        );

        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('data', $resultado);
        $this->assertEquals('Mensaje con botones enviado correctamente', $resultado['message']);
    }

    /** @test */
    public function maneja_errores_de_api_en_botones()
    {
        Http::fake([
            '*' => Http::response([
                'error' => 'API Error'
            ], 500)
        ]);

        $resultado = $this->whatsappService->sendButtons(
            '5492942506803',
            'Título',
            'Descripción',
            'Footer',
            [['type' => 'reply', 'displayText' => 'Información recibida', 'id' => 'info_recibida']]
        );

        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Error al enviar mensaje con botones', $resultado['message']);
    }

    /** @test */
    public function formatea_numero_correctamente_en_botones()
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200)
        ]);

        $resultado = $this->whatsappService->sendButtons(
            '2942506803',
            'Título',
            'Descripción',
            'Footer',
            [['type' => 'reply', 'displayText' => 'Información recibida', 'id' => 'info_recibida']]
        );

        $this->assertTrue($resultado['success']);

        $resultado2 = $this->whatsappService->sendButtons(
            '5492942506803',
            'Título',
            'Descripción',
            'Footer',
            [['type' => 'reply', 'displayText' => 'Información recibida', 'id' => 'info_recibida']]
        );

        $this->assertTrue($resultado2['success']);
    }

    /** @test */
    public function puede_enviar_documento()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'doc123'
            ], 200)
        ]);

        $resultado = $this->whatsappService->sendDocument(
            '5492942506803',
            'https://ejemplo.com/documento.pdf',
            'test.pdf',
            'Documento de prueba'
        );

        $this->assertTrue($resultado['success']);
        $this->assertArrayHasKey('data', $resultado);
    }

    /** @test */
    public function puede_enviar_imagen()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message_id' => 'img123'
            ], 200)
        ]);

        $resultado = $this->whatsappService->sendImage(
            '5492942506803',
            'https://ejemplo.com/imagen.jpg',
            'Imagen de prueba'
        );

        $this->assertTrue($resultado['success']);
    }

    /** @test */
    public function maneja_errores_de_api()
    {
        Http::fake([
            '*' => Http::response([
                'error' => 'API Error'
            ], 500)
        ]);

        $resultado = $this->whatsappService->sendTextMessage(
            '5492942506803',
            'Mensaje de prueba'
        );

        $this->assertFalse($resultado['success']);
        $this->assertArrayHasKey('error', $resultado);
    }

    /** @test */
    public function formatea_numero_correctamente()
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200)
        ]);

        // Test con número simple
        $resultado = $this->whatsappService->sendTextMessage(
            '5492942506803',
            'Test'
        );

        $this->assertTrue($resultado['success']);

        // Test con número ya formateado
        $resultado2 = $this->whatsappService->sendTextMessage(
            '5492942506803@s.whatsapp.net',
            'Test'
        );

        $this->assertTrue($resultado2['success']);
    }

    /** @test */
    public function valida_configuracion()
    {
        $validacion = $this->whatsappService->validateConfiguration();

        $this->assertTrue($validacion['valid']);
        $this->assertArrayHasKey('config', $validacion);
    }

    /** @test */
    public function detecta_configuracion_invalida()
    {
        config([
            'services.whatsapp.api_url' => null,
            'services.whatsapp.instance_id' => null
        ]);

        $service = new WhatsAppService();
        $validacion = $service->validateConfiguration();

        $this->assertFalse($validacion['valid']);
        $this->assertNotEmpty($validacion['errors']);
    }

    /** @test */
    public function puede_enviar_mensaje_personalizado()
    {
        Http::fake([
            '*' => Http::response(['success' => true], 200)
        ]);

        $payload = [
            'key' => [
                'remoteJid' => '5492942506803@s.whatsapp.net',
                'fromMe' => true,
                'id' => 'TEST123'
            ],
            'message' => [
                'conversation' => 'Test message'
            ],
            'messageType' => 'conversation'
        ];

        $resultado = $this->whatsappService->sendCustomMessage($payload);

        $this->assertTrue($resultado['success']);
    }

    /** @test */
    public function rechaza_mensaje_personalizado_sin_campos_requeridos()
    {
        $payload = [
            'message' => [
                'conversation' => 'Test'
            ]
            // Falta key.remoteJid
        ];

        $resultado = $this->whatsappService->sendCustomMessage($payload);

        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('remoteJid', $resultado['error']);
    }

    /** @test */
    public function puede_conectar_instancia()
    {
        Http::fake([
            '*' => Http::response([
                'pairingCode' => null,
                'code' => '2@exemple',
                'base64' => 'data:image/png;base64,abc123',
                'count' => 1,
            ], 200)
        ]);

        $resultado = $this->whatsappService->connect('test-instance-id');

        $this->assertTrue($resultado['success']);
        $this->assertEquals('data:image/png;base64,abc123', $resultado['base64']);
        $this->assertArrayHasKey('data', $resultado);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/instance/connect/test-instance-id');
        });
    }

    /** @test */
    public function maneja_errores_al_conectar_instancia()
    {
        Http::fake([
            '*' => Http::response([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Instance not found'],
            ], 404)
        ]);

        $resultado = $this->whatsappService->connect('instancia-inexistente');

        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Error al conectar', $resultado['message']);
    }

    /** @test */
    public function puede_desconectar_instancia()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'message' => 'Instance logged out successfully',
            ], 200)
        ]);

        $resultado = $this->whatsappService->logout('test-instance-id');

        $this->assertTrue($resultado['success']);
        $this->assertStringContainsString('desconectada', $resultado['message']);

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE' && str_contains($request->url(), '/instance/logout/test-instance-id');
        });
    }

    /** @test */
    public function maneja_errores_al_desconectar_instancia()
    {
        Http::fake([
            '*' => Http::response([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Instance not found'],
            ], 404)
        ]);

        $resultado = $this->whatsappService->logout('instancia-inexistente');

        $this->assertFalse($resultado['success']);
        $this->assertStringContainsString('Error al desconectar', $resultado['message']);
    }

    /** @test */
    public function puede_obtener_instancias_del_servidor()
    {
        Http::fake([
            '*' => Http::response([
                ['instance' => ['instanceName' => 'instancia-1', 'status' => 'open']],
                ['instance' => ['instanceName' => 'instancia-2', 'status' => 'close']],
            ], 200)
        ]);

        $resultado = $this->whatsappService->getInstances();

        $this->assertTrue($resultado['success']);
        $this->assertCount(2, $resultado['data']);
    }
}
