<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'nome' => 'Administrador',
                'descricao' => 'Gerencia usuários, permissões e configurações do sistema.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Gestor de Almoxarifado',
                'descricao' => 'Gerencia materiais, estoque, entradas e retiradas.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nome' => 'Servidor',
                'descricao' => 'Consulta materiais e solicita retiradas do almoxarifado.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

