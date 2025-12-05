<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Ventum;

class DetalleVentaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DetalleVenta::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'venta_id' => Ventum::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => fake()->randomFloat(2, 0, 99999999.99),
            'precio_unitario' => fake()->randomFloat(2, 0, 99999999.99),
            'descuento' => fake()->randomFloat(2, 0, 99999999.99),
            'subtotal' => fake()->randomFloat(2, 0, 99999999.99),
        ];
    }
}
