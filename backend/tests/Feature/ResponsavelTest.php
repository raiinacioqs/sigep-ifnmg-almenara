<?php

namespace Tests\Feature;

use App\Models\Responsavel;
use App\Models\Role;
use App\Models\Setor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsavelTest extends TestCase
{
    use RefreshDatabase;

    private function autenticarComo(
        string $perfil = 'Administrador'
    ): void {
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

    private function criarSetor(
        string $status = 'ativo'
    ): Setor {
        return Setor::create([
            'nome' => 'Tecnologia da Informação',
            'sigla' => 'TI',
            'localizacao' => 'Bloco Administrativo',
            'status' => $status,
        ]);
    }

    private function dadosResponsavel(
        int $setorId,
        array $sobrescrever = []
    ): array {
        return array_merge([
            'siape' => '123456789',
            'nome' => 'João da Silva',
            'email' => 'joao@ifnmg.edu.br',
            'cargo' => 'Técnico em Tecnologia da Informação',
            'setor_id' => $setorId,
            'status' => 'ativo',
        ], $sobrescrever);
    }

    public function test_administrador_consegue_cadastrar_responsavel(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )
            ->assertCreated()
            ->assertJsonPath('data.nome', 'João da Silva')
            ->assertJsonPath('data.setor.sigla', 'TI');

        $this->assertDatabaseHas('responsaveis', [
            'siape' => '123456789',
            'email' => 'joao@ifnmg.edu.br',
            'status' => 'ativo',
        ]);
    }

    public function test_gestor_consegue_cadastrar_responsavel(): void
    {
        $this->autenticarComo('Gestor de Almoxarifado');

        $setor = $this->criarSetor();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertCreated();

        $this->assertDatabaseCount('responsaveis', 1);
    }

    public function test_nao_permite_cadastrar_responsavel_com_siape_duplicado(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $dados = $this->dadosResponsavel($setor->id);

        $this->postJson('/api/responsaveis', $dados)
            ->assertCreated();

        $this->postJson('/api/responsaveis', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('siape');

        $this->assertDatabaseCount('responsaveis', 1);
    }

    public function test_responsavel_sem_nome_nao_e_cadastrado(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $dados = $this->dadosResponsavel($setor->id);
        unset($dados['nome']);

        $this->postJson('/api/responsaveis', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('nome');

        $this->assertDatabaseCount('responsaveis', 0);
    }

    public function test_responsavel_sem_email_nao_e_cadastrado(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $dados = $this->dadosResponsavel($setor->id);
        unset($dados['email']);

        $this->postJson('/api/responsaveis', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('responsaveis', 0);
    }

    public function test_nao_permite_cadastrar_responsavel_com_email_invalido(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id, [
                'email' => 'email-invalido',
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('responsaveis', 0);
    }

    public function test_responsavel_sem_cargo_nao_e_cadastrado(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $dados = $this->dadosResponsavel($setor->id);
        unset($dados['cargo']);

        $this->postJson('/api/responsaveis', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cargo');

        $this->assertDatabaseCount('responsaveis', 0);
    }

    public function test_nao_permite_cadastrar_responsavel_com_setor_inexistente(): void
    {
        $this->autenticarComo();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel(999)
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('setor_id');

        $this->assertDatabaseCount('responsaveis', 0);
    }

    public function test_nao_permite_cadastrar_responsavel_em_setor_inativo(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor('inativo');

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('setor_id');

        $this->assertDatabaseCount('responsaveis', 0);
    }

    public function test_consegue_consultar_responsavel_com_dados_do_setor(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $cadastro = $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->getJson("/api/responsaveis/{$id}")
            ->assertOk()
            ->assertJsonPath('data.nome', 'João da Silva')
            ->assertJsonPath('data.setor.id', $setor->id)
            ->assertJsonPath('data.setor.nome', 'Tecnologia da Informação');
    }

    public function test_consegue_listar_responsaveis(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertCreated();

        $this->getJson('/api/responsaveis')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_filtrar_responsaveis_por_status(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertCreated();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id, [
                'siape' => '987654321',
                'nome' => 'Maria Oliveira',
                'email' => 'maria@ifnmg.edu.br',
                'status' => 'inativo',
            ])
        )->assertCreated();

        $this->getJson('/api/responsaveis?status=ativo')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_filtrar_responsaveis_por_setor(): void
    {
        $this->autenticarComo();

        $setorTI = $this->criarSetor();

        $setorRH = Setor::create([
            'nome' => 'Recursos Humanos',
            'sigla' => 'RH',
            'localizacao' => 'Bloco Administrativo',
            'status' => 'ativo',
        ]);

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setorTI->id)
        )->assertCreated();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setorRH->id, [
                'siape' => '987654321',
                'nome' => 'Maria Oliveira',
                'email' => 'maria@ifnmg.edu.br',
            ])
        )->assertCreated();

        $this->getJson("/api/responsaveis?setor_id={$setorTI->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_buscar_responsavel_por_nome_siape_ou_email(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertCreated();

        $this->getJson('/api/responsaveis?busca=João')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/responsaveis?busca=123456789')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/responsaveis?busca=joao@ifnmg.edu.br')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_consegue_atualizar_responsavel(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $cadastro = $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson(
            "/api/responsaveis/{$id}",
            $this->dadosResponsavel($setor->id, [
                'nome' => 'João Atualizado',
            ])
        )
            ->assertOk()
            ->assertJsonPath('data.nome', 'João Atualizado');

        $this->assertDatabaseHas('responsaveis', [
            'id' => $id,
            'nome' => 'João Atualizado',
        ]);
    }

    public function test_atualizacao_permite_manter_o_proprio_siape(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $cadastro = $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->putJson(
            "/api/responsaveis/{$id}",
            $this->dadosResponsavel($setor->id, [
                'nome' => 'João Alterado',
            ])
        )->assertOk();

        $this->assertDatabaseCount('responsaveis', 1);
    }

    public function test_consegue_inativar_responsavel_sem_exclui_lo(): void
    {
        $this->autenticarComo();

        $setor = $this->criarSetor();

        $cadastro = $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertCreated();

        $id = $cadastro->json('data.id');

        $this->deleteJson("/api/responsaveis/{$id}")
            ->assertOk();

        $this->assertDatabaseHas('responsaveis', [
            'id' => $id,
            'status' => 'inativo',
        ]);

        $this->assertDatabaseHas('responsaveis', [
            'id' => $id,
            'siape' => '123456789',
        ]);
    }

    public function test_servidor_nao_consegue_cadastrar_responsavel(): void
    {
        $this->autenticarComo('Servidor');

        $setor = $this->criarSetor();

        $this->postJson(
            '/api/responsaveis',
            $this->dadosResponsavel($setor->id)
        )->assertForbidden();

        $this->assertDatabaseCount('responsaveis', 0);
    }
}
