<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetorTest extends TestCase
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

    private function dadosSetor(array $sobrescrever = []): array
    {
        return array_merge([
            'nome' => 'Tecnologia da Informação',
            'sigla' => 'TI',
            'localizacao' => 'Bloco Administrativo',
            'status' => 'ativo',
        ], $sobrescrever);
    }

    public function test_administrador_consegue_cadastrar_setor(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/setores', $this->dadosSetor())
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Tecnologia da Informação');

        $this->assertDatabaseHas('setores', [
            'sigla' => 'TI',
            'status' => 'ativo',
        ]);
    }

    public function test_nao_permite_cadastrar_setor_com_sigla_duplicada(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosSetor();

        $this->postJson('/api/setores', $dados)
            ->assertCreated();

        $this->postJson('/api/setores', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sigla');

        $this->assertDatabaseCount('setores', 1);
    }

    public function test_setor_sem_nome_nao_e_cadastrado(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosSetor();
        unset($dados['nome']);

        $this->postJson('/api/setores', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nome');

        $this->assertDatabaseCount('setores', 0);
    }

    public function test_setor_sem_sigla_nao_e_cadastrado(): void
    {
        $this->autenticarComo();

        $dados = $this->dadosSetor();
        unset($dados['sigla']);

        $this->postJson('/api/setores', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sigla');

        $this->assertDatabaseCount('setores', 0);
    }

    public function test_consegue_consultar_setor_cadastrado(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/setores',
            $this->dadosSetor()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->getJson("/api/setores/{$id}")
            ->assertOk()
            ->assertJsonPath('data.sigla', 'TI');
    }

    public function test_consegue_listar_setores(): void
    {
        $this->autenticarComo();

        $this->postJson(
            '/api/setores',
            $this->dadosSetor()
        )->assertCreated();

        $this->getJson('/api/setores')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_filtrar_setores_por_status(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/setores', $this->dadosSetor())
            ->assertCreated();

        $this->postJson('/api/setores', $this->dadosSetor([
            'nome' => 'Recursos Humanos',
            'sigla' => 'RH',
            'status' => 'inativo',
        ]))->assertCreated();

        $this->getJson('/api/setores?status=ativo')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_buscar_setor_por_nome_ou_sigla(): void
    {
        $this->autenticarComo();

        $this->postJson('/api/setores', $this->dadosSetor())
            ->assertCreated();

        $this->getJson('/api/setores?busca=TI')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_atualizar_setor(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/setores',
            $this->dadosSetor()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson("/api/setores/{$id}", $this->dadosSetor([
            'nome' => 'Tecnologia e Sistemas',
        ]))
            ->assertOk()
            ->assertJsonPath('data.nome', 'Tecnologia e Sistemas');

        $this->assertDatabaseHas('setores', [
            'id' => $id,
            'nome' => 'Tecnologia e Sistemas',
        ]);
    }

    public function test_atualizacao_permite_manter_a_propria_sigla(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/setores',
            $this->dadosSetor()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson("/api/setores/{$id}", $this->dadosSetor([
            'nome' => 'Tecnologia Atualizada',
        ]))
            ->assertOk();

        $this->assertDatabaseCount('setores', 1);
    }

    public function test_consegue_inativar_setor_sem_exclui_lo(): void
    {
        $this->autenticarComo();

        $cadastro = $this->postJson(
            '/api/setores',
            $this->dadosSetor()
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->deleteJson("/api/setores/{$id}")
            ->assertOk();

        $this->assertDatabaseHas('setores', [
            'id' => $id,
            'status' => 'inativo',
        ]);

        $this->assertDatabaseHas('setores', [
            'id' => $id,
            'sigla' => 'TI',
        ]);
    }

    public function test_servidor_nao_consegue_cadastrar_setor(): void
    {
        $this->autenticarComo('Servidor');

        $this->postJson('/api/setores', $this->dadosSetor())
            ->assertForbidden();

        $this->assertDatabaseCount('setores', 0);
    }
}
