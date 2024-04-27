<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\FormatoRegistroRecibo;

class FormatoRegistroReciboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FormatoRegistroRecibo::create([
            'tipo'=>'ingresos',
            'codigo'=> 'codigo_basico',
            'descripcion'=>'Basico',
            'cantidad'=>'cantidad_basico',
            'importe'=>'monto_basico',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'deducciones',
            'codigo'=> 'codigo_aporte_jubilacion',
            'descripcion'=>'Jubilacion',
            'cantidad'=>'cantidad_aporte_jubilacion',
            'importe'=>'monto_aporte_jubilacion',
            'empresa_id'=>1
        ]);
        FormatoRegistroRecibo::create([
            'tipo'=>'total',
            'codigo'=> '',
            'descripcion'=>'Neto',
            'cantidad'=>'',
            'importe'=>'neto',
            'empresa_id'=>1
        ]);
    }
}
