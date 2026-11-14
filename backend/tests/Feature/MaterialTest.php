<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Estoque;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    private function autenticarComo(string $perfil = 'Administrador'): void
    {
        $role = Role::create([
            'nome' => $perfil,
            'descricao' => 'Perfil utilizado nos testes',
        ]);

        $usuario = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'ativo',
        ]);

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token);
    }

    private function criarCategoria(array $sobrescrever = []): Categoria
    {
        return Categoria::create(array_merge([
            'nome' => 'Materiais de Escritório',
            'descricao' => 'Categoria utilizada nos testes',
            'tipo' => 'consumo',
            'status' => 'ativo',
        ], $sobrescrever));
    }

    private function dadosMaterial(array $sobrescrever = []): array
    {
        $categoria = $this->criarCategoria();

        return array_merge([
            'nome' => 'Papel A4',
            'descricao' => 'Papel branco formato A4',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 15,
            'status' => 'ativo',
        ], $sobrescrever);
    }

    public function test_administrador_consegue_cadastrar_material(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/materiais', $this->dadosMaterial())
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Papel A4')
            ->assertJsonPath('data.status', 'ativo');

        $this->assertDatabaseHas('materiais', [
            'nome' => 'Papel A4',
            'status' => 'ativo',
        ]);
    }

    public function test_gestor_consegue_cadastrar_material(): void
    {
        $this->autenticarComo('Gestor de Almoxarifado');

        $this->postJson('/api/materiais', $this->dadosMaterial())
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Papel A4');
    }

    public function test_cadastro_cria_estoque_inicial_com_quantidade_zero(): void
    {
        $this->autenticarComo();

        $resposta = $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $materialId = $resposta->json('data.id');

        $this->assertDatabaseHas('estoques', [
            'material_id' => $materialId,
            'quantidade_atual' => 0,
        ]);

        $this->assertNotNull($resposta->json('data.estoque'));
        $this->assertEquals(
            0,
            (float) $resposta->json('data.estoque.quantidade_atual')
        );
    }

    public function test_nao_permite_cadastrar_material_com_categoria_patrimonial(): void
    {
        $this->autenticarComo();

        $categoria = $this->criarCategoria([
            'tipo' => 'patrimonio',
        ]);

        $dados = [
            'nome' => 'Papel A4',
            'descricao' => 'Papel branco formato A4',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 15,
        ];

        $this->postJson('/api/materiais', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categoria_id');

        $this->assertDatabaseCount('materiais', 0);
    }

    public function test_nao_permite_cadastrar_material_com_categoria_inativa(): void
    {
        $this->autenticarComo();

        $categoria = $this->criarCategoria([
            'status' => 'inativo',
        ]);

        $dados = [
            'nome' => 'Papel A4',
            'descricao' => 'Papel branco formato A4',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 15,
        ];

        $this->postJson('/api/materiais', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categoria_id');

        $this->assertDatabaseCount('materiais', 0);
    }

    public function test_material_sem_nome_nao_e_cadastrado(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosMaterial();
        unset($dados['nome']);

        $this->postJson('/api/materiais', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nome');

        $this->assertDatabaseCount('materiais', 0);
    }

    public function test_material_sem_categoria_nao_e_cadastrado(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosMaterial();
        unset($dados['categoria_id']);

        $this->postJson('/api/materiais', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('categoria_id');

        $this->assertDatabaseCount('materiais', 0);
    }

    public function test_nao_permite_quantidade_minima_negativa(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosMaterial([
            'quantidade_minima' => -1,
        ]);

        $this->postJson('/api/materiais', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantidade_minima');

        $this->assertDatabaseCount('materiais', 0);
    }

    public function test_servidor_nao_consegue_cadastrar_material(): void
    {
        $this->autenticarComo('Servidor');

        $this->postJson('/api/materiais', $this->dadosMaterial())
            ->assertForbidden();

        $this->assertDatabaseCount('materiais', 0);
    }


    public function test_consegue_consultar_material_com_categoria_e_estoque(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->getJson("/api/materiais/{$id}")
            ->assertOk()
            ->assertJsonPath('data.nome', 'Papel A4')
            ->assertJsonPath('data.categoria.nome', 'Materiais de Escritório')
            ->assertJsonPath('data.estoque.quantidade_atual', '0.000');
    }

    public function test_consegue_listar_materiais(): void
    {
        $this->autenticarComo();

        $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $this->getJson('/api/materiais')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_filtrar_materiais_por_status_e_categoria(): void
    {
        $this->autenticarComo();

        $categoria = $this->criarCategoria();

        $this->postJson('/api/materiais', [
            'nome' => 'Papel A4',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 15,
            'status' => 'ativo',
        ])->assertCreated();

        $this->postJson('/api/materiais', [
            'nome' => 'Caneta Azul',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'Unidade',
            'quantidade_minima' => 10,
            'status' => 'inativo',
        ])->assertCreated();

        $this->getJson("/api/materiais?status=ativo&categoria_id={$categoria->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Papel A4');
    }

    public function test_consegue_buscar_material_por_nome_ou_descricao(): void
    {
        $this->autenticarComo();

        $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $this->getJson('/api/materiais?busca=Papel')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/materiais?busca=branco formato')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }


    public function test_consegue_atualizar_material(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson("/api/materiais/{$id}", [
            'nome' => 'Papel A4 Atualizado',
            'descricao' => 'Papel branco de melhor qualidade',
            'categoria_id' => $cadastro->json('data.categoria_id'),
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 20,
            'status' => 'ativo',
        ])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Papel A4 Atualizado');

        $this->assertDatabaseHas('materiais', [
            'id' => $id,
            'nome' => 'Papel A4 Atualizado',
            'quantidade_minima' => 20,
        ]);
    }

    public function test_atualizacao_nao_altera_quantidade_atual_do_estoque(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        \App\Models\Estoque::where('material_id', $id)
            ->update(['quantidade_atual' => 50]);

        $this->putJson("/api/materiais/{$id}", [
            'nome' => 'Papel A4 Atualizado',
            'descricao' => 'Descrição atualizada',
            'categoria_id' => $cadastro->json('data.categoria_id'),
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 25,
            'status' => 'ativo',
        ])->assertOk();

        $this->assertDatabaseHas('estoques', [
            'material_id' => $id,
            'quantidade_atual' => 50,
        ]);
    }

    public function test_atualizacao_permite_manter_a_propria_categoria(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson("/api/materiais/{$id}", [
            'nome' => 'Papel A4 Revisado',
            'descricao' => 'Descrição revisada',
            'categoria_id' => $cadastro->json('data.categoria_id'),
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 15,
            'status' => 'ativo',
        ])->assertOk();

        $this->assertDatabaseCount('materiais', 1);
    }

    public function test_consegue_inativar_material_sem_exclui_lo(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->deleteJson("/api/materiais/{$id}")
            ->assertOk();

        $this->assertDatabaseHas('materiais', [
            'id' => $id,
            'status' => 'inativo',
        ]);

        $this->assertDatabaseHas('estoques', [
            'material_id' => $id,
        ]);
    }


    public function test_servidor_nao_consegue_atualizar_material(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        auth()->forgetGuards();

        $this->autenticarComo('Servidor');

        $this->putJson("/api/materiais/{$id}", [
            'nome' => 'Material Alterado',
            'descricao' => 'Tentativa de alteração',
            'categoria_id' => $cadastro->json('data.categoria_id'),
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 15,
            'status' => 'ativo',
        ])->assertForbidden();

        $this->assertDatabaseHas('materiais', [
            'id' => $id,
            'nome' => 'Papel A4',
        ]);
    }

    public function test_servidor_nao_consegue_inativar_material(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/materiais',
            $this->dadosMaterial()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        auth()->forgetGuards();

        $this->autenticarComo('Servidor');

        $this->deleteJson("/api/materiais/{$id}")
            ->assertForbidden();

        $this->assertDatabaseHas('materiais', [
            'id' => $id,
            'status' => 'ativo',
        ]);
    }

    public function test_usuario_nao_autenticado_nao_consegue_listar_materiais(): void
    {
        $this->getJson('/api/materiais')
            ->assertUnauthorized();
    }

}
