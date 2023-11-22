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
            'nombre' => 'ejemplo',
            'cuit' => 20358337164,
        ]);

        empresa::factory()->count(20)->create();


           
    }
}
