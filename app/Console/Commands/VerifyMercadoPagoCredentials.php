<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;

class VerifyMercadoPagoCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mp:verify {empresa_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar las credenciales de MercadoPago para una empresa';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $empresaId = $this->argument('empresa_id');
        
        $empresa = Empresa::find($empresaId);
        
        if (!$empresa) {
            $this->error("Empresa con ID {$empresaId} no encontrada.");
            return 1;
        }

        $this->info("Verificando credenciales de: {$empresa->nombre_empresa}");
        $this->newLine();

        // Verificar que las credenciales existan
        $accessToken = $empresa->MP_ACCESS_TOKEN;
        $userId = $empresa->MP_USER_ID;

        if (empty($accessToken)) {
            $this->error('❌ Access Token no configurado');
            return 1;
        }

        if (empty($userId)) {
            $this->error('❌ User ID no configurado');
            return 1;
        }

        // Validar formato del token
        $this->line("Validando formato del Access Token...");
        $tokenLength = strlen($accessToken);
        
        if ($tokenLength < 60) {
            $this->error("❌ El Access Token parece ser incorrecto (longitud: {$tokenLength} caracteres)");
            $this->error("   Token: " . substr($accessToken, 0, 40) . '...');
            $this->warn("   Se esperan al menos 60 caracteres para un token válido");
            return 1;
        }

        $this->info("✅ Formato del token correcto (longitud: {$tokenLength} caracteres)");
        $this->line("   Token: " . substr($accessToken, 0, 40) . '...');
        $this->newLine();

        // Probar conexión con la API
        $this->line("Probando conexión con API de MercadoPago...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->get('https://api.mercadopago.com/users/me');

            if ($response->successful()) {
                $userData = $response->json();
                
                $this->info("✅ Conexión exitosa!");
                $this->newLine();
                $this->info("Información de la cuenta:");
                $this->line("  User ID: " . ($userData['id'] ?? 'N/A'));
                $this->line("  Email: " . ($userData['email'] ?? 'N/A'));
                $this->line("  Nickname: " . ($userData['nickname'] ?? 'N/A'));
                $this->line("  First Name: " . ($userData['first_name'] ?? 'N/A'));
                $this->line("  Last Name: " . ($userData['last_name'] ?? 'N/A'));
                
                // Verificar que el User ID coincida
                if (isset($userData['id']) && $userData['id'] != $userId) {
                    $this->newLine();
                    $this->warn("⚠️  ATENCIÓN: El User ID configurado ({$userId}) no coincide con el de la API ({$userData['id']})");
                    $this->warn("   Considere actualizar el User ID en la base de datos.");
                }
                
                $this->newLine();
                $this->info("🎉 Las credenciales son válidas y están funcionando correctamente!");
                
                return 0;
            } else {
                $this->error("❌ Error al conectar con la API:");
                $this->line("   Status: " . $response->status());
                $this->line("   Response: " . $response->body());
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Error al verificar credenciales:");
            $this->line("   " . $e->getMessage());
            return 1;
        }
    }
}
