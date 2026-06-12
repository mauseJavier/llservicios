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
        Empresa::firstOrCreate(
            ['cuit' => 20358337164],
            [
                'nombre' => 'EmpresaPrueba',
                'correo' => 'empresaprueba@example.com',
            ]
        );

        if(env('APP_DEBUG')==true){//para cuando estoy en produccion
            $empresas = [
                ['nombre' => 'EmpresaDemo1', 'cuit' => 30111111111, 'correo' => 'demo1@example.com'],
                ['nombre' => 'EmpresaDemo2', 'cuit' => 30222222222, 'correo' => 'demo2@example.com'],
                ['nombre' => 'EmpresaDemo3', 'cuit' => 30333333333, 'correo' => 'demo3@example.com'],
            ];

            foreach ($empresas as $empresa) {
                Empresa::firstOrCreate(
                    ['cuit' => $empresa['cuit']],
                    $empresa
                );
            }
        }
        


           
    }
}
