<?php

namespace Tests\Feature;

use App\Models\Empresa;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_scheduler_registra_los_comandos_principales(): void
    {
        Empresa::create([
            'nombre' => 'Empresa Test',
            'cuit' => '12345678901',
            'correo' => 'empresa@test.com',
            'dia_notificacion' => 15,
        ]);

        Artisan::call('schedule:list');
        $output = Artisan::output();

        $this->assertStringContainsString('app:cobrador-hora', $output);
        $this->assertStringContainsString('app:cobrador-diario', $output);
        $this->assertStringContainsString('app:cobrador-semanal', $output);
        $this->assertStringContainsString('app:cobrador-mensual', $output);
        $this->assertStringContainsString('app:aplicar-incremento-mora', $output);
        $this->assertStringContainsString('mp:reconciliar-pagos', $output);
    }

    public function test_el_scheduler_registra_las_notificaciones_por_empresa(): void
    {
        $empresa = Empresa::create([
            'nombre' => 'Empresa Notificaciones',
            'cuit' => '10987654321',
            'correo' => 'notificaciones@test.com',
            'dia_notificacion' => 20,
        ]);

        // routes/console.php se evalúa durante el boot de la aplicación, antes
        // de que RefreshDatabase cree las tablas. Se vuelve a evaluar con la
        // empresa ya persistida para comprobar el calendario por empresa.
        require base_path('routes/console.php');

        $eventos = collect(app(Schedule::class)->events());

        $notificaciones = $eventos->filter(
            fn ($evento) => str_contains((string) $evento->command, 'app:notificacion-mensual')
        );

        $this->assertTrue(
            $notificaciones->contains(
                fn ($evento) => str_contains((string) $evento->command, (string) $empresa->id)
            ),
            'No se registró la notificación mensual para la empresa.'
        );

        $this->assertTrue(
            $eventos->contains(
                fn ($evento) => str_contains((string) $evento->command, 'app:notificacion-mensual-ws')
            ),
            'No se registró la notificación mensual por WhatsApp.'
        );
    }
}
