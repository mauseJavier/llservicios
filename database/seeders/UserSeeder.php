<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // LOS AGREGO AK PARA QUE FUNCIONE EN PRODUCCION
        DB::table('users')->insert([
            'name' => 'DESMARET JAVIER NICOLAS',
                'email' => 'mause.javi@gmail.com',
                'dni' => '35833716',
                'password'=> Hash::make(1234),
                'role_id'=> 3
            ]);
            
            DB::table('users')->insert([
            'name' => 'Marcelo Gimenez',
                'email' => 'marce_nqn_19@hotmail.com',
                'dni' => '35079663',
                'password'=> Hash::make(1234),
                'role_id'=> 3
            ]);

            if(env('APP_DEBUG')==true){//para cuando estoy en produccion
                    User::factory()->count(100)->create();
            }
    }
}
