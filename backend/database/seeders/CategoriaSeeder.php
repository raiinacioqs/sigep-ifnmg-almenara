<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nome' => 'Informática',
                'tipo' => 'patrimonio',
                'descricao' => 'Equipamentos de informática',
            ],
            [
                'nome' => 'Mobiliário',
                'tipo' => 'patrimonio',
                'descricao' => 'Móveis e itens de escritório',
            ],
            [
                'nome' => 'Equipamentos Audiovisuais',
                'tipo' => 'patrimonio',
                'descricao' => 'Projetores, telas e equipamentos de áudio',
            ],
            [
                'nome' => 'Material de Expediente',
                'tipo' => 'consumo',
                'descricao' => 'Materiais utilizados nas atividades administrativas',
            ],
            [
                'nome' => 'Material de Limpeza',
                'tipo' => 'consumo',
                'descricao' => 'Produtos e materiais de limpeza',
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(
                [
                    'nome' => $categoria['nome'],
                    'tipo' => $categoria['tipo'],
                ],
                [
                    'descricao' => $categoria['descricao'],
                    'status' => 'ativo',
                ]
            );
        }
    }
}