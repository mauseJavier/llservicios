<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;



class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(RoleSeeder::class);
        $this->call(EmpresaSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(FormaPagoSeeder::class);

        if(env('APP_DEBUG')==true){//para cuando estoy en produccion
            
            
            $this->call(ClienteSeeder::class);
            $this->call(ServicioSeeder::class);
            $this->call(ClienteServicio::class);
            $this->call(ClienteEmpresaSeeder::class);
            $this->call(ServicioPagarSeeder::class);
            
        }
       
    }
}
