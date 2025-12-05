<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Categorium;
use App\Models\Producto;

class ProductoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Producto::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->regexify('[A-Za-z0-9]{100}'),
            'nombre' => fake()->regexify('[A-Za-z0-9]{255}'),
            'descripcion' => fake()->text(),
            'precio' => fake()->randomFloat(2, 0, 99999999.99),
            'costo' => fake()->randomFloat(2, 0, 99999999.99),
            'stock' => fake()->numberBetween(-10000, 10000),
            'stock_minimo' => fake()->numberBetween(-10000, 10000),
            'categoria_id' => Categorium::factory(),
            'activo' => fake()->boolean(),
        ];
    }
}
