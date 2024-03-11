<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\empresa;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        empresa::create([
            'nombre' => 'EmpresaPrueba',
            'cuit' => 20358337164,
        ]);

        if(env('APP_DEBUG')==true){//para cuando estoy en produccion
            empresa::factory()->count(20)->create();
        }
        


           
    }
}
