<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $categoria = Categoria::where('tipo', 'consumo')
            ->first();

        if (!$categoria) {
            $this->command->error(
                'Nenhuma categoria de consumo foi encontrada.'
            );

            return;
        }

        $materiais = [
            [
                'nome' => 'Papel A4',
                'descricao' => 'Resma de papel sulfite A4',
                'unidade_medida' => 'resma',
                'quantidade_minima' => 5,
            ],
            [
                'nome' => 'Caneta esferográfica azul',
                'descricao' => 'Caneta esferográfica de tinta azul',
                'unidade_medida' => 'unidade',
                'quantidade_minima' => 20,
            ],
            [
                'nome' => 'Lápis grafite',
                'descricao' => 'Lápis grafite para escrita',
                'unidade_medida' => 'unidade',
                'quantidade_minima' => 10,
            ],
            [
                'nome' => 'Pasta suspensa',
                'descricao' => 'Pasta suspensa para arquivamento',
                'unidade_medida' => 'unidade',
                'quantidade_minima' => 10,
            ],
            [
                'nome' => 'Toner HP LaserJet',
                'descricao' => 'Toner para impressora HP LaserJet',
                'unidade_medida' => 'unidade',
                'quantidade_minima' => 2,
            ],
        ];

        foreach ($materiais as $material) {
            Material::firstOrCreate(
                [
                    'nome' => $material['nome'],
                    'categoria_id' => $categoria->id,
                ],
                [
                    'descricao' => $material['descricao'],
                    'unidade_medida' => $material['unidade_medida'],
                    'quantidade_minima' => $material['quantidade_minima'],
                    'status' => 'ativo',
                ]
            );
        }

        $this->command->info(
            'Materiais de teste cadastrados com sucesso!'
        );
    }
}