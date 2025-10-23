<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Empresa;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Empresa::create([
            'nombre' => 'EmpresaPrueba',
            'cuit' => 20358337164,
            'correo' => 'empresaprueba@example.com',
        ]);

        if(env('APP_DEBUG')==true){//para cuando estoy en produccion
            Empresa::factory()->count(20)->create();
        }
        


           
    }
}
