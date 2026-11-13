<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaTest extends TestCase
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

    private function dadosCategoria(array $sobrescrever = []): array
    {
        return array_merge([
            'nome' => 'Material de Escritório',
            'descricao' => 'Categoria de materiais administrativos',
            'tipo' => 'consumo',
            'status' => 'ativo',
        ], $sobrescrever);
    }

    public function test_administrador_consegue_cadastrar_categoria(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/categorias', $this->dadosCategoria())
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Material de Escritório');

        $this->assertDatabaseHas('categorias', [
            'nome' => 'Material de Escritório',
            'tipo' => 'consumo',
            'status' => 'ativo',
        ]);
    }

    public function test_categoria_sem_nome_nao_e_cadastrada(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosCategoria();
        unset($dados['nome']);

        $this->postJson('/api/categorias', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nome');
    }

    public function test_categoria_com_tipo_invalido_nao_e_cadastrada(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/categorias', $this->dadosCategoria([
            'tipo' => 'invalido',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tipo');
    }

    public function test_nao_permite_categoria_duplicada_com_mesmo_tipo(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosCategoria();

        $this->postJson('/api/categorias', $dados)
            ->assertCreated();

        $this->postJson('/api/categorias', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nome');
    }

    public function test_permite_mesmo_nome_em_tipos_diferentes(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/categorias', $this->dadosCategoria([
            'nome' => 'Equipamentos',
            'tipo' => 'patrimonio',
        ]))->assertCreated();

        $this->postJson('/api/categorias', $this->dadosCategoria([
            'nome' => 'Equipamentos',
            'tipo' => 'consumo',
        ]))->assertCreated();

        $this->assertDatabaseCount('categorias', 2);
    }

    public function test_consegue_consultar_categoria_cadastrada(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/categorias',
            $this->dadosCategoria()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->getJson("/api/categorias/{$id}")
            ->assertOk()
            ->assertJsonPath('data.nome', 'Material de Escritório');
    }

    public function test_consegue_atualizar_categoria(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/categorias',
            $this->dadosCategoria()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson("/api/categorias/{$id}", $this->dadosCategoria([
            'nome' => 'Materiais Administrativos',
        ]))
            ->assertOk()
            ->assertJsonPath('data.nome', 'Materiais Administrativos');

        $this->assertDatabaseHas('categorias', [
            'id' => $id,
            'nome' => 'Materiais Administrativos',
        ]);
    }

    public function test_consegue_filtrar_categorias_por_tipo(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/categorias', $this->dadosCategoria([
            'nome' => 'Equipamentos',
            'tipo' => 'patrimonio',
        ]))->assertCreated();

        $this->postJson('/api/categorias', $this->dadosCategoria([
            'nome' => 'Papelaria',
            'tipo' => 'consumo',
        ]))->assertCreated();

        $this->getJson('/api/categorias?tipo=patrimonio')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Equipamentos');
    }

    public function test_consegue_inativar_categoria_sem_exclui_la(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/categorias',
            $this->dadosCategoria()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->deleteJson("/api/categorias/{$id}")
            ->assertOk();

        $this->assertDatabaseHas('categorias', [
            'id' => $id,
            'status' => 'inativo',
        ]);

        $this->assertDatabaseHas('categorias', [
            'id' => $id,
            'nome' => 'Material de Escritório',
        ]);
    }

    public function test_servidor_nao_consegue_cadastrar_categoria(): void
    {
        $this->autenticarComo('Servidor');

        $this->postJson('/api/categorias', $this->dadosCategoria())
            ->assertForbidden();

        $this->assertDatabaseCount('categorias', 0);
    }
}