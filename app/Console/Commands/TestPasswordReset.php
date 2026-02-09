<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class TestPasswordReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:password-reset {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un email de prueba de recuperación de contraseña a un usuario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("🔍 Buscando usuario con email: {$email}");
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("❌ No se encontró ningún usuario con el email: {$email}");
            $this->newLine();
            $this->info("💡 Usuarios disponibles:");
            User::take(5)->get(['id', 'name', 'email'])->each(function($u) {
                $this->line("   - {$u->name} ({$u->email})");
            });
            return 1;
        }
        
        $this->info("✅ Usuario encontrado: {$user->name}");
        $this->newLine();
        
        $this->info("📧 Enviando email de recuperación de contraseña...");
        
        $status = Password::sendResetLink(['email' => $email]);
        
        if ($status === Password::RESET_LINK_SENT) {
            $this->newLine();
            $this->info("✅ ¡Email enviado exitosamente!");
            $this->newLine();
            $this->info("📬 Revisa el email en Mailpit:");
            $this->line("   URL: http://localhost:8025");
            $this->newLine();
            
            // Mostrar token generado
            $token = \DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();
            
            if ($token) {
                $this->info("🔑 Token generado (para debug):");
                $this->line("   Email: {$token->email}");
                $this->line("   Creado: {$token->created_at}");
                $this->newLine();
            }
            
            return 0;
        }
        
        $this->error("❌ Error al enviar el email: " . __($status));
        return 1;
    }
}
