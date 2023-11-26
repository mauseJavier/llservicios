<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServicioPagarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?,?)', 
        [1, 1, 333.33, date('y-m-d H:i:s'),date('y-m-d H:i:s')]);
        DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?,?)', 
        [1, 2, 444.33, date('y-m-d H:i:s'),date('y-m-d H:i:s')]);
        DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?,?)', 
        [1, 3, 555.33, date('y-m-d H:i:s'),date('y-m-d H:i:s')]);
        DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?,?)', 
        [2, 1, 666.33, date('y-m-d H:i:s'),date('y-m-d H:i:s')]);
        DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?,?)', 
        [2, 1, 777.33, date('y-m-d H:i:s'),date('y-m-d H:i:s')]);
        DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?,?)', 
        [3, 1, 888.33, date('y-m-d H:i:s'),date('y-m-d H:i:s')]);
        DB::insert('INSERT INTO `servicio_pagar`(`cliente_id`, `servicio_id`, `precio`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?,?)', 
        [3, 1, 999.33, date('y-m-d H:i:s'),date('y-m-d H:i:s')]);
    }
}
