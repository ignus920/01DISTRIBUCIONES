<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Cliente;

class ClienteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cliente::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->regexify('[A-Za-z0-9]{200}'),
            'email' => fake()->safeEmail(),
            'telefono' => fake()->regexify('[A-Za-z0-9]{20}'),
            'direccion' => fake()->text(),
            'ciudad' => fake()->regexify('[A-Za-z0-9]{100}'),
            'nit' => fake()->regexify('[A-Za-z0-9]{50}'),
            'tipo' => fake()->randomElement(["natural","juridico"]),
            'activo' => fake()->boolean(),
        ];
    }
}
