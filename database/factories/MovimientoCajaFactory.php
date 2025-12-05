<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Ventum;

class MovimientoCajaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MovimientoCaja::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'caja_id' => Caja::factory(),
            'tipo' => fake()->randomElement(["ingreso","egreso"]),
            'concepto' => fake()->regexify('[A-Za-z0-9]{255}'),
            'monto' => fake()->randomFloat(2, 0, 99999999.99),
            'fecha' => fake()->dateTime(),
            'usuario_id' => Usuario::factory(),
            'venta_id' => Ventum::factory(),
            'observaciones' => fake()->text(),
            'user_id' => User::factory(),
        ];
    }
}
