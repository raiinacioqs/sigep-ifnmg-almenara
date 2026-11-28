<?php

namespace Database\Factories;

use App\Models\BemPatrimonial;
use App\Models\Categoria;
use App\Models\Fornecedor;
use App\Models\Responsavel;
use App\Models\Setor;
use Illuminate\Database\Eloquent\Factories\Factory;

class BemPatrimonialFactory extends Factory
{
    protected $model = BemPatrimonial::class;

    public function definition(): array
    {
        $categoriaId = Categoria::query()
            ->where('tipo', 'patrimonio')
            ->inRandomOrder()
            ->value('id');

        $setorId = Setor::query()
            ->inRandomOrder()
            ->value('id');

        if (!$categoriaId || !$setorId) {
            throw new \RuntimeException(
                'Cadastre pelo menos uma categoria e um setor antes de gerar os bens.'
            );
        }

        return [
            'numero_tombo' => fake()->unique()->bothify('PAT-2026-####'),
            'descricao' => fake()->randomElement([
                'Computador Desktop Dell',
                'Notebook Lenovo',
                'Impressora HP LaserJet',
                'Projetor Epson',
                'Monitor LED 24 polegadas',
                'Teclado USB',
                'Mouse USB',
                'Cadeira de escritório',
                'Mesa de escritório',
                'Ar-condicionado Split',
            ]),

            'categoria_id' => $categoriaId,

            'fornecedor_id' => Fornecedor::query()
                ->inRandomOrder()
                ->value('id'),

            'setor_id' => $setorId,

            'responsavel_id' => Responsavel::query()
                ->inRandomOrder()
                ->value('id'),

            'localizacao' => fake()->randomElement([
                'Sala 01',
                'Sala 02',
                'Laboratório de Informática',
                'Biblioteca',
                'Almoxarifado',
                'Sala da Administração',
            ]),

            'nota_fiscal' => fake()->numerify('NF-2026-#####'),

            'data_aquisicao' => fake()->dateTimeBetween(
                '-5 years',
                'now'
            )->format('Y-m-d'),

            'valor_aquisicao' => fake()->randomFloat(
                2,
                100,
                8000
            ),

            'estado_conservacao' => fake()->randomElement([
                'novo',
                'bom',
                'regular',
            ]),

            'status' => 'ativo',
        ];
    }
}
