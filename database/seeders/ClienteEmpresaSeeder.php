<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;

class ClienteEmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [1, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);

        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [2, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);

        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [3, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);

        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [4, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);

        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [5, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);

        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [6, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);

        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [7, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);

        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [8, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);

        DB::insert('INSERT INTO `cliente_empresa`(`cliente_id`, `empresa_id`, `created_at`, `updated_at`) 
        VALUES (?,?,?,?)',
        [9, 1,date('y-m-d H:i:s'),date('y-m-d H:i:s'),]);
    }
}
