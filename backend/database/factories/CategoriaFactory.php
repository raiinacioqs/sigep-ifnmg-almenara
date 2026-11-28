<?php

namespace Database\Factories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->words(2, true),

            'descricao' => fake()->sentence(),

            'tipo' => fake()->randomElement([
                'patrimonio',
                'consumo',
            ]),

            'status' => 'ativo',
        ];
    }
}