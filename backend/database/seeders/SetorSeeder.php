<?php

namespace Database\Seeders;

use App\Models\Setor;
use Illuminate\Database\Seeder;

class SetorSeeder extends Seeder
{
    public function run(): void
    {
        $setores = [
            [
                'nome' => 'Tecnologia da Informação',
                'sigla' => 'TI',
                'localizacao' => 'Campus Almenara',
            ],
            [
                'nome' => 'Administração',
                'sigla' => 'ADM',
                'localizacao' => 'Campus Almenara',
            ],
            [
                'nome' => 'Biblioteca',
                'sigla' => 'BIB',
                'localizacao' => 'Campus Almenara',
            ],
            [
                'nome' => 'Coordenação de Ensino',
                'sigla' => 'CEN',
                'localizacao' => 'Campus Almenara',
            ],
            [
                'nome' => 'Laboratório de Informática',
                'sigla' => 'LAB',
                'localizacao' => 'Campus Almenara',
            ],
        ];

        foreach ($setores as $setor) {
            Setor::firstOrCreate(
                ['sigla' => $setor['sigla']],
                [
                    'nome' => $setor['nome'],
                    'localizacao' => $setor['localizacao'],
                    'status' => 'ativo',
                ]
            );
        }
    }
}