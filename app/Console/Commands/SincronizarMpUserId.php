<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SincronizarMpUserId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mp:sincronizar-user-id {--force : Recalcular incluso si la empresa ya tiene MP_USER_ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Autocompleta el MP_USER_ID de cada empresa consultando /users/me con su token';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Sincronizando MP_USER_ID de las empresas...');

        $empresas = Empresa::whereNotNull('MP_ACCESS_TOKEN')
            ->where('MP_ACCESS_TOKEN', '!=', '')
            ->get();

        if ($empresas->isEmpty()) {
            $this->info('No hay empresas con MP_ACCESS_TOKEN configurado.');
            return 0;
        }

        $actualizadas = 0;
        $sinCambios = 0;
        $fallos = 0;

        foreach ($empresas as $empresa) {
            if (!empty($empresa->MP_USER_ID) && !$this->option('force')) {
                $sinCambios++;
                continue;
            }

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $empresa->MP_ACCESS_TOKEN,
                    'Content-Type' => 'application/json',
                ])->get('https://api.mercadopago.com/users/me');

                if ($response->successful()) {
                    $userId = (string) ($response->json('id') ?? '');

                    if ($userId === '') {
                        $this->warn("Empresa {$empresa->id} ({$empresa->nombre}): respuesta sin id de usuario.");
                        $fallos++;
                        continue;
                    }

                    $empresa->update(['MP_USER_ID' => $userId]);
                    $this->info("Empresa {$empresa->id} ({$empresa->nombre}): MP_USER_ID = {$userId}");
                    $actualizadas++;

                    Log::info('MP_USER_ID sincronizado', [
                        'empresa_id' => $empresa->id,
                        'mp_user_id' => $userId,
                    ]);
                } else {
                    $this->warn("Empresa {$empresa->id} ({$empresa->nombre}): error HTTP {$response->status()}.");
                    $fallos++;
                }
            } catch (\Throwable $e) {
                $this->warn("Empresa {$empresa->id} ({$empresa->nombre}): {$e->getMessage()}");
                $fallos++;
            }
        }

        $this->info("Sincronización finalizada: actualizadas={$actualizadas}, ya_configuradas={$sinCambios}, fallos={$fallos}");

        return 0;
    }
}