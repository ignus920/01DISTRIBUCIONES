<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Usuario;
use App\Models\Venta;

class VentaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Venta::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'numero' => fake()->regexify('[A-Za-z0-9]{50}'),
            'fecha' => fake()->dateTime(),
            'cliente_id' => Cliente::factory(),
            'usuario_id' => Usuario::factory(),
            'subtotal' => fake()->randomFloat(2, 0, 99999999.99),
            'descuento' => fake()->randomFloat(2, 0, 99999999.99),
            'impuesto' => fake()->randomFloat(2, 0, 99999999.99),
            'total' => fake()->randomFloat(2, 0, 99999999.99),
            'estado' => fake()->randomElement(["pendiente","pagada","anulada"]),
            'tipo_pago' => fake()->randomElement(["efectivo","tarjeta","transferencia"]),
            'observaciones' => fake()->text(),
            'user_id' => User::factory(),
        ];
    }
}
