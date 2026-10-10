<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use Illuminate\Console\Command;

class GenerarTokenEmpresa extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empresa:token {empresa : ID o nombre de la empresa} {--force : Regenerar el token aunque ya exista}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera (o rota) el token global de API de una empresa';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = $this->argument('empresa');

        $empresa = is_numeric($identifier)
            ? Empresa::find($identifier)
            : Empresa::where('nombre', $identifier)->first();

        if (! $empresa) {
            $this->error("No se encontró ninguna empresa con identificador '{$identifier}'.");

            return self::FAILURE;
        }

        if (! empty($empresa->api_token) && ! $this->option('force')) {
            $this->warn("La empresa '{$empresa->nombre}' (ID {$empresa->id}) ya tiene un token generado.");
            $this->line('Usá --force para regenerarlo. Esto invalidará el token anterior.');

            return self::FAILURE;
        }

        $plainToken = $empresa->generateApiToken();

        $this->info("Token generado para la empresa '{$empresa->nombre}' (ID {$empresa->id}).");
        $this->newLine();
        $this->line('Guardá este token ahora, no se volverá a mostrar:');
        $this->newLine();
        $this->line($plainToken);
        $this->newLine();
        $this->line('Usalo en el header: Authorization: Bearer <token>');

        return self::SUCCESS;
    }
}
