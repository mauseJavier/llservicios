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
}
