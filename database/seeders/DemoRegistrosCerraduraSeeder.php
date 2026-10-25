<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\RegistroCerradura;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoRegistrosCerraduraSeeder extends Seeder
{
    /**
     * Marca los registros generados por este seeder para poder regenerarlos
     * sin duplicar datos ni tocar los registros reales.
     */
    private const MARCADOR = 'demo-cerradura';

    /**
     * Genera accesos de demostración para todos los clientes de una empresa,
     * repartidos en el mes actual y los tres meses anteriores.
     *
     * Uso:
     *   php artisan db:seed --class=DemoRegistrosCerraduraSeeder
     */
    public function run(): void
    {
        $empresa = Empresa::find(1) ?? Empresa::first();

        if (! $empresa) {
            $this->command?->warn('No hay empresas para generar registros de cerradura.');

            return;
        }

        $clientes = $empresa->clientes()->orderBy('clientes.id')->get();

        if ($clientes->isEmpty()) {
            $this->command?->warn("La empresa {$empresa->nombre} no tiene clientes vinculados.");

            return;
        }

        $eliminados = $this->eliminarGenerados($empresa->id);

        $cerraduras = ['Portón principal', 'Puerta de oficina', 'Acceso al depósito', 'Garage'];
        $dispositivos = ['DLL-001', 'DLL-002', 'DLL-003'];
        $metodos = ['huella', 'tarjeta', 'PIN', 'reconocimiento facial'];

        // Días hábiles con acceso por mes: 8 del mes actual + 6 + 5 + 4.
        // Cada día genera entrada y salida, así que cada cliente supera los 15 registros.
        $plan = [
            ['meses_atras' => 0, 'dias' => 8, 'hasta_hoy' => true],
            ['meses_atras' => 1, 'dias' => 6],
            ['meses_atras' => 2, 'dias' => 5],
            ['meses_atras' => 3, 'dias' => 4],
        ];

        $creados = 0;

        foreach ($clientes as $indice => $cliente) {
            $cerradura = $cerraduras[$indice % count($cerraduras)];
            $dispositivo = $dispositivos[$indice % count($dispositivos)];

            foreach ($plan as $bloque) {
                $inicio = now()->startOfMonth()->subMonthsNoOverflow($bloque['meses_atras']);
                $fin = ! empty($bloque['hasta_hoy'])
                    ? now()->copy()->endOfDay()
                    : $inicio->copy()->endOfMonth();

                $dias = $this->diasHabiles($inicio, $fin);

                if ($dias === []) {
                    continue;
                }

                shuffle($dias);
                $dias = array_slice($dias, 0, $bloque['dias']);

                // Aseguramos accesos hoy para que el calendario abra con datos.
                if (! empty($bloque['hasta_hoy'])) {
                    $dias[] = now()->copy()->startOfDay();
                }

                $dias = collect($dias)
                    ->unique(fn (Carbon $dia) => $dia->format('Y-m-d'))
                    ->values();

                foreach ($dias as $dia) {
                    $metodo = $metodos[array_rand($metodos)];

                    $entrada = $dia->copy()->setTime(random_int(7, 9), random_int(0, 59));
                    $salida = $dia->copy()->setTime(random_int(17, 20), random_int(0, 59));

                    $this->crear($empresa->id, $cliente->id, $cerradura, $dispositivo, $entrada, 'entrada', $metodo);
                    $this->crear($empresa->id, $cliente->id, $cerradura, $dispositivo, $salida, 'salida', $metodo);
                    $creados += 2;

                    // Algunas jornadas tienen un tercer acceso al mediodía.
                    if (random_int(1, 3) === 1) {
                        $extra = $dia->copy()->setTime(random_int(12, 14), random_int(0, 59));

                        $this->crear(
                            $empresa->id,
                            $cliente->id,
                            $cerradura,
                            $dispositivo,
                            $extra,
                            random_int(0, 1) === 1 ? 'entrada' : 'salida',
                            $metodos[array_rand($metodos)]
                        );
                        $creados++;
                    }
                }
            }
        }

        $this->command?->info(
            "Registros de cerradura demo: {$creados} generados para {$clientes->count()} cliente(s) "
            ."de {$empresa->nombre} ({$eliminados} anteriores reemplazados)."
        );
    }

    /**
     * Elimina solo los registros creados por este seeder.
     */
    private function eliminarGenerados(int $empresaId): int
    {
        $ids = RegistroCerradura::where('empresa_id', $empresaId)
            ->get()
            ->filter(fn (RegistroCerradura $registro) => ($registro->datos_extra['seed'] ?? null) === self::MARCADOR)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return RegistroCerradura::whereIn('id', $ids)->delete();
    }

    /**
     * Días hábiles (lunes a viernes) dentro del rango indicado.
     *
     * @return array<int, Carbon>
     */
    private function diasHabiles(Carbon $inicio, Carbon $fin): array
    {
        $dias = [];

        for ($dia = $inicio->copy()->startOfDay(); $dia->lte($fin); $dia->addDay()) {
            if ($dia->isWeekday()) {
                $dias[] = $dia->copy();
            }
        }

        return $dias;
    }

    private function crear(
        int $empresaId,
        int $clienteId,
        string $cerradura,
        string $dispositivo,
        Carbon $fecha,
        string $tipo,
        string $metodo
    ): void {
        RegistroCerradura::create([
            'empresa_id' => $empresaId,
            'cliente_id' => $clienteId,
            'nombre_cerradura' => $cerradura,
            'tipo' => $tipo,
            'dispositivo_id' => $dispositivo,
            'fecha_hora' => $fecha,
            'recibido_en' => $fecha->copy()->addSeconds(random_int(1, 20)),
            'datos_extra' => [
                'metodo' => $metodo,
                'seed' => self::MARCADOR,
            ],
        ]);
    }
}
