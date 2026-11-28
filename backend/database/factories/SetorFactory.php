<?php

namespace Database\Factories;

use App\Models\Setor;
use Illuminate\Database\Eloquent\Factories\Factory;

class SetorFactory extends Factory
{
    protected $model = Setor::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->company(),

            'sigla' => fake()->unique()->regexify('[A-Z]{3}[0-9]{2}'),

            'localizacao' => fake()->city(),

            'status' => 'ativo',
        ];
    }
}