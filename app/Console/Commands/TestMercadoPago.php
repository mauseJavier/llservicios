<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MercadoPago\MercadoPagoService;

class TestMercadoPago extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mercadopago:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la integración con MercadoPago';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== PROBANDO INTEGRACIÓN MERCADOPAGO ===');
        
        // Verificar configuración
        $this->info('Verificando configuración...');
        $config = config('services.mercadopago');
        
        $this->table(['Configuración', 'Valor'], [
            ['Public Key', $config['public_key'] ? 'Configurado' : 'No configurado'],
            ['Access Token', $config['access_token'] ? 'Configurado' : 'No configurado'],
            ['Sandbox', $config['sandbox'] ? 'true' : 'false'],
            ['APP_URL', config('app.url')],
            ['Entorno', config('app.env')]
        ]);

        // Validar credenciales
        $this->info('Validando credenciales...');
        $mercadoPagoService = new MercadoPagoService();
        $credentialValidation = $mercadoPagoService->validateCredentials();
        
        if ($credentialValidation['valid']) {
            $this->info('✅ ' . $credentialValidation['message']);
        } else {
            $this->error('❌ Error en credenciales: ' . $credentialValidation['error']);
            return 1;
        }

        // Probar URLs
        $this->info('Probando generación de URLs...');
        $urls = MercadoPagoService::getValidUrls();
        $this->table(['Tipo', 'URL'], [
            ['Success', $urls['success']],
            ['Failure', $urls['failure']],
            ['Pending', $urls['pending']],
            ['Webhook', $urls['webhook']]
        ]);

        // Probar creación de preferencia
        $this->info('Creando preferencia de prueba...');
        
        $testData = [
            'items' => [
                [
                    'title' => 'Producto de Prueba - Artisan',
                    'quantity' => 1,
                    'unit_price' => 150.00,  // Cambiar de 'price' a 'unit_price'
                    'currency_id' => 'ARS',
                    'description' => 'Prueba desde comando artisan'
                ]
            ],
            'external_reference' => 'ARTISAN-TEST-' . time(),
            'payer' => [
                'name' => 'Test',
                'surname' => 'User',
                'email' => 'test@example.com',
                'phone' => ['number' => '123456789']
            ],
            'back_urls' => [
                'success' => $urls['success'],
                'failure' => $urls['failure'],
                'pending' => $urls['pending'],
            ],
            'auto_return' => 'approved',
            'notification_url' => $urls['webhook'],
        ];

        $result = $mercadoPagoService->createPreference($testData);

        if ($result['success']) {
            $this->info('✅ Preferencia creada exitosamente!');
            $this->table(['Campo', 'Valor'], [
                ['Preference ID', $result['preference_id']],
                ['Init Point (Producción)', $result['init_point']],
                ['Sandbox Init Point', $result['sandbox_init_point']]
            ]);
            
            $checkoutUrl = config('services.mercadopago.sandbox') 
                ? $result['sandbox_init_point'] 
                : $result['init_point'];
                
            $this->info("🔗 URL de Checkout: {$checkoutUrl}");
            
        } else {
            $this->error('❌ Error creando preferencia:');
            $this->error($result['error']);
            
            if (isset($result['error_code'])) {
                $this->error("Código de error: {$result['error_code']}");
            }
        }

        return 0;
    }
}