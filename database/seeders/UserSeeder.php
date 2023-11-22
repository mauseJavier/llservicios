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
        //
        DB::table('users')->insert([
            'name' => 'DESMARET JAVIER NICOLAS',
             'email' => 'mause.javi@gmail.com',
             'dni' => '35833716',
             'password'=> Hash::make(1234),
             'role_id'=> 3
         ]);
         
         User::factory()->count(100)->create();
    }
}
