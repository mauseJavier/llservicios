<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Servicio>
 */
class ServicioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'descripcion'=> fake()->name(). ' Una descripcion de prueba' ,     
            'precio'=> rand(1,10000)+ (rand(1,100)/100),       
            'empresa_id'=> rand(1, 20)
        ];
    }
}
