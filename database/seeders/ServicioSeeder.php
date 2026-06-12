<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Servicio;

class ServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $servicios = [
            ['nombre' => 'Consulta General', 'descripcion' => 'Atención médica general', 'precio' => 1500.00, 'empresa_id' => 1],
            ['nombre' => 'Análisis de Sangre', 'descripcion' => 'Análisis clínico completo', 'precio' => 2500.00, 'empresa_id' => 1],
            ['nombre' => 'Ecografía', 'descripcion' => 'Ecografía abdominal', 'precio' => 3500.00, 'empresa_id' => 1],
            ['nombre' => 'Consulta Cardiológica', 'descripcion' => 'Evaluación del corazón', 'precio' => 2000.00, 'empresa_id' => 1],
            ['nombre' => 'Rayos X', 'descripcion' => 'Radiografía digital', 'precio' => 1800.00, 'empresa_id' => 1],
        ];

        foreach ($servicios as $servicio) {
            Servicio::firstOrCreate(
                ['nombre' => $servicio['nombre'], 'empresa_id' => $servicio['empresa_id']],
                $servicio
            );
        }
    }
}
