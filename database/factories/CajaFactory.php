<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Caja;
use App\Models\User;
use App\Models\Usuario;

class CajaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Caja::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->regexify('[A-Za-z0-9]{100}'),
            'saldo_inicial' => fake()->randomFloat(2, 0, 99999999.99),
            'saldo_actual' => fake()->randomFloat(2, 0, 99999999.99),
            'fecha_apertura' => fake()->dateTime(),
            'fecha_cierre' => fake()->dateTime(),
            'usuario_id' => Usuario::factory(),
            'estado' => fake()->randomElement(["abierta","cerrada"]),
            'observaciones' => fake()->text(),
            'user_id' => User::factory(),
        ];
    }
}
