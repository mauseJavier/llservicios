<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;

class UpdateMercadoPagoCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mp:update-credentials {empresa_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar las credenciales de MercadoPago para una empresa';

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

        $this->info("Empresa encontrada: {$empresa->nombre_empresa}");
        $this->newLine();
        
        // Mostrar credenciales actuales
        $this->warn("Credenciales actuales:");
        $this->line("Access Token: " . ($empresa->MP_ACCESS_TOKEN ? substr($empresa->MP_ACCESS_TOKEN, 0, 30) . '...' : 'No configurado'));
        $this->line("Public Key: " . ($empresa->MP_PUBLIC_KEY ? substr($empresa->MP_PUBLIC_KEY, 0, 30) . '...' : 'No configurado'));
        $this->line("User ID: " . ($empresa->MP_USER_ID ?: 'No configurado'));
        $this->newLine();

        // Solicitar nuevas credenciales
        $accessToken = $this->ask('Ingrese el Access Token completo (APP_USR-...)');
        
        if (!$accessToken || strlen($accessToken) < 60) {
            $this->error('El Access Token debe tener al menos 60 caracteres. Verifique que sea el token completo.');
            return 1;
        }

        $publicKey = $this->ask('Ingrese el Public Key (APP_USR-...)');
        
        if (!$publicKey || strlen($publicKey) < 40) {
            $this->error('El Public Key debe tener al menos 40 caracteres.');
            return 1;
        }

        $userId = $this->ask('Ingrese el User ID (números solamente)');
        
        if (!$userId || !is_numeric($userId)) {
            $this->error('El User ID debe ser un número.');
            return 1;
        }

        // Confirmar actualización
        if (!$this->confirm('¿Desea actualizar estas credenciales?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        // Actualizar en la base de datos
        $empresa->update([
            'MP_ACCESS_TOKEN' => $accessToken,
            'MP_PUBLIC_KEY' => $publicKey,
            'MP_USER_ID' => $userId,
        ]);

        $this->newLine();
        $this->info('✅ Credenciales actualizadas exitosamente!');
        $this->newLine();
        
        // Mostrar resumen
        $this->info("Nuevas credenciales:");
        $this->line("Access Token: " . substr($accessToken, 0, 30) . '... (longitud: ' . strlen($accessToken) . ')');
        $this->line("Public Key: " . substr($publicKey, 0, 30) . '...');
        $this->line("User ID: " . $userId);
        
        return 0;
    }
}
