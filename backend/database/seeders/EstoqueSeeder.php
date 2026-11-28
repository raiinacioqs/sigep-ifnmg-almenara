<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstoqueSeeder extends Seeder
{
    public function run(): void
    {
        $materiais = Material::whereIn('nome', [
            'Papel A4',
            'Caneta esferográfica azul',
            'Lápis grafite',
            'Pasta suspensa',
            'Toner HP LaserJet',
        ])->get();

        foreach ($materiais as $material) {
            DB::table('estoques')->insertOrIgnore([
                'material_id' => $material->id,
                'quantidade_atual' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info(
            'Saldos de estoque de teste cadastrados!'
        );
    }
}