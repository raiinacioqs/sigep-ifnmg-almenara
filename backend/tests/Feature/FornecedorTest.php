<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FornecedorTest extends TestCase
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

    private function dadosFornecedor(array $sobrescrever = []): array
    {
        return array_merge([
            'razao_social' => 'Fornecedor Exemplo LTDA',
            'nome_fantasia' => 'Fornecedor Exemplo',
            'cnpj' => '11222333000181',
            'email' => 'contato@fornecedor.com',
            'telefone' => '(33) 3333-3333',
            'endereco' => 'Rua Principal, 100',
            'status' => 'ativo',
        ], $sobrescrever);
    }

    public function test_administrador_consegue_cadastrar_fornecedor(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/fornecedores', $this->dadosFornecedor())
            ->assertCreated()
            ->assertJsonPath('data.razao_social', 'Fornecedor Exemplo LTDA');

        $this->assertDatabaseHas('fornecedores', [
            'cnpj' => '11222333000181',
            'status' => 'ativo',
        ]);
    }

    public function test_nao_permite_cadastrar_fornecedor_com_cnpj_invalido(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/fornecedores', $this->dadosFornecedor([
            'cnpj' => '12345678901234',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cnpj');

        $this->assertDatabaseCount('fornecedores', 0);
    }

    public function test_nao_permite_cadastrar_fornecedor_com_cnpj_duplicado(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosFornecedor();

        $this->postJson('/api/fornecedores', $dados)
            ->assertCreated();

        $this->postJson('/api/fornecedores', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cnpj');

        $this->assertDatabaseCount('fornecedores', 1);
    }

    public function test_fornecedor_sem_razao_social_nao_e_cadastrado(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosFornecedor();
        unset($dados['razao_social']);

        $this->postJson('/api/fornecedores', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('razao_social');

        $this->assertDatabaseCount('fornecedores', 0);
    }

    public function test_consegue_consultar_fornecedor_cadastrado(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/fornecedores',
            $this->dadosFornecedor()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->getJson("/api/fornecedores/{$id}")
            ->assertOk()
            ->assertJsonPath('data.razao_social', 'Fornecedor Exemplo LTDA');
    }

    public function test_consegue_listar_fornecedores(): void
    {
        $this->autenticarComo();

        $this->postJson(
            '/api/fornecedores',
            $this->dadosFornecedor()
        )->assertCreated();

        $this->getJson('/api/fornecedores')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_atualizar_fornecedor(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/fornecedores',
            $this->dadosFornecedor()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson("/api/fornecedores/{$id}", $this->dadosFornecedor([
            'razao_social' => 'Fornecedor Atualizado LTDA',
        ]))
            ->assertOk()
            ->assertJsonPath('data.razao_social', 'Fornecedor Atualizado LTDA');

        $this->assertDatabaseHas('fornecedores', [
            'id' => $id,
            'razao_social' => 'Fornecedor Atualizado LTDA',
        ]);
    }

    public function test_atualizacao_permite_manter_o_proprio_cnpj(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/fornecedores',
            $this->dadosFornecedor()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson("/api/fornecedores/{$id}", $this->dadosFornecedor([
            'razao_social' => 'Fornecedor Alterado LTDA',
        ]))
            ->assertOk();

        $this->assertDatabaseCount('fornecedores', 1);
    }

    public function test_consegue_inativar_fornecedor_sem_exclui_lo(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/fornecedores',
            $this->dadosFornecedor()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->deleteJson("/api/fornecedores/{$id}")
            ->assertOk();

        $this->assertDatabaseHas('fornecedores', [
            'id' => $id,
            'status' => 'inativo',
        ]);

        $this->assertDatabaseHas('fornecedores', [
            'id' => $id,
            'cnpj' => '11222333000181',
        ]);
    }

    public function test_servidor_nao_consegue_cadastrar_fornecedor(): void
    {
        $this->autenticarComo('Servidor');

        $this->postJson('/api/fornecedores', $this->dadosFornecedor())
            ->assertForbidden();

        $this->assertDatabaseCount('fornecedores', 0);
    }
}