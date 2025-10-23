<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Empresa>
 */
class EmpresaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'cuit' => fake()->unique()->numberBetween(20000000000, 30999999999), // CUIT válido para empresas
            'correo' => fake()->unique()->safeEmail(),
        ];
    }
}
