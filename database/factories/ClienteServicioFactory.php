<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class ClienteServicioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => rand(1, 20),
            'servicio_id' => rand(1, 20),
            'precio' => fake()->randomFloat($nbMaxDecimals = 2, $min = 10, $max = 100),
            'vencimiento'=> fake()->dateTimeThisCentury()->format('Y-m-d H:i:s'),
        ];
    }
}
