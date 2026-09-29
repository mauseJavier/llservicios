<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Cliente;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientes = [
            ['nombre' => 'Juan Pérez', 'dni' => 12345678, 'correo' => 'juan@example.com', 'telefono' => '+541112345678'],
            ['nombre' => 'María García', 'dni' => 23456789, 'correo' => 'maria@example.com', 'telefono' => '+541112345679'],
            ['nombre' => 'Carlos López', 'dni' => 34567890, 'correo' => 'carlos@example.com', 'telefono' => '+541112345680'],
            ['nombre' => 'Ana Martínez', 'dni' => 45678901, 'correo' => 'ana@example.com', 'telefono' => '+541112345681'],
            ['nombre' => 'Pedro Rodríguez', 'dni' => 56789012, 'correo' => 'pedro@example.com', 'telefono' => '+541112345682'],
        ];

        foreach ($clientes as $cliente) {
            Cliente::firstOrCreate(
                ['dni' => $cliente['dni']],
                $cliente
            );
        }
    }
}
