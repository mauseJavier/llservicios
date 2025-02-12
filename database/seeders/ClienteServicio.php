<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\DB;


class ClienteServicio extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::insert('insert into cliente_servicio 
            (cliente_id, servicio_id, vencimiento) values (?, ?, ?)',
             [1, 1,'14/11/23']);

             DB::insert('insert into cliente_servicio 
             (cliente_id, servicio_id, vencimiento) values (?, ?, ?)',
              [1, 2,'14/11/23']);

              DB::insert('insert into cliente_servicio 
              (cliente_id, servicio_id, vencimiento) values (?, ?, ?)',
               [2, 1,'14/11/23']);

               

    }
}
