<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

class TestMercadoPagoMinimal extends Command
{
    protected $signature = 'mercadopago:test-minimal';
    protected $description = 'Prueba mínima de MercadoPago';

    public function handle()
    {
        $this->info('=== PRUEBA MÍNIMA MERCADOPAGO ===');
        
        // Mostrar configuración
        $accessToken = config('services.mercadopago.access_token');
        $sandbox = config('services.mercadopago.sandbox');
        
        $this->info("Access Token: " . ($accessToken ? 'Configurado (' . substr($accessToken, 0, 20) . '...)' : 'No configurado'));
        $this->info("Sandbox: " . ($sandbox ? 'true' : 'false'));
        
        try {
            // Configurar MercadoPago
            MercadoPagoConfig::setAccessToken($accessToken);
            
            if ($sandbox) {
                MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
            }
            
            $this->info('✅ Configuración de MercadoPago establecida');
            
            // Crear cliente
            $client = new PreferenceClient();
            $this->info('✅ Cliente de preferencias creado');
            
            // Datos mínimos requeridos
            $preferenceData = [
                "items" => [
                    [
                        "title" => "Test Product",
                        "quantity" => 1,
                        "unit_price" => 100.0,
                        "currency_id" => "ARS"
                    ]
                ]
            ];
            
            $this->info('Enviando datos a MercadoPago...');
            $this->line(json_encode($preferenceData, JSON_PRETTY_PRINT));
            
            // Crear preferencia
            $preference = $client->create($preferenceData);
            
            $this->info('✅ ¡Preferencia creada exitosamente!');
            $this->info("ID: " . $preference->id);
            $this->info("Init Point: " . $preference->init_point);
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            
            // Intentar obtener más detalles
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                $this->error('Respuesta: ' . json_encode($response, JSON_PRETTY_PRINT));
            }
            
            // Verificar conectividad
            $this->info('Verificando conectividad...');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($result === false) {
                $this->error('❌ No se puede conectar a api.mercadopago.com');
            } else {
                $this->info("✅ Conectividad OK (HTTP $httpCode)");
            }
            
            return 1;
        }
        
        return 0;
    }
}