<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Models\Usuario;

class MovimientoInventarioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MovimientoInventario::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'tipo' => fake()->randomElement(["entrada","salida","ajuste"]),
            'cantidad' => fake()->randomFloat(2, 0, 99999999.99),
            'motivo' => fake()->regexify('[A-Za-z0-9]{255}'),
            'fecha' => fake()->dateTime(),
            'usuario_id' => Usuario::factory(),
            'observaciones' => fake()->text(),
            'user_id' => User::factory(),
        ];
    }
}
