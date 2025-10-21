<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FormaPago;

class FormaPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        FormaPago::create([
            'nombre' => 'Efectivo',            
        ]);
        FormaPago::create([
            'nombre' => 'Tarjeta',            
        ]);
        FormaPago::create([
            'nombre' => 'MercadoPago',            
        ]);

        
    }
}
