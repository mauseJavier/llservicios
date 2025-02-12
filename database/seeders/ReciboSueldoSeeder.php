<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\ReciboSueldo;

class ReciboSueldoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReciboSueldo::create([
            'periodo'=>'01-01-2024',
            'empleador'=>'munisipalidad',
            'cuil'=>1,
            'legajo'=>12345,
            
        ]);
    }
}
