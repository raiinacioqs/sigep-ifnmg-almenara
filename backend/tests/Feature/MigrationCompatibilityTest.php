<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_sigep_tables_are_created_successfully(): void
    {
        $tabelas = [
            'users',
            'roles',
            'setores',
            'responsaveis',
            'categorias',
            'fornecedores',
            'materiais',
            'estoques',
            'entradas_estoque',
            'itens_entrada_estoque',
            'solicitacoes_retirada',
            'itens_solicitacao_retirada',
            'movimentacoes_estoque',
            'personal_access_tokens',
            'bens_patrimoniais',
            'movimentacoes_patrimoniais',
            'manutencoes',
            'solicitacoes_acesso',
        ];

        foreach ($tabelas as $tabela) {
            $this->assertTrue(
                Schema::hasTable($tabela),
                "A tabela {$tabela} não foi criada."
            );
        }
    }
}
