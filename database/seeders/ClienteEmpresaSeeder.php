<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

use App\Models\Cliente;

class ClienteEmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresaId = 1;

        // Mismos DNIs de demostración que crea ClienteSeeder
        $dnisDemo = [12345678, 23456789, 34567890, 45678901, 56789012];

        $clientes = Cliente::whereIn('dni', $dnisDemo)->get();

        foreach ($clientes as $cliente) {
            DB::table('cliente_empresa')->updateOrInsert(
                [
                    'cliente_id' => $cliente->id,
                    'empresa_id' => $empresaId,
                ],
                [
                    'aplicar_recargos' => 0,
                    'created_at' => date('y-m-d H:i:s'),
                    'updated_at' => date('y-m-d H:i:s'),
                ]
            );
        }
    }
}
