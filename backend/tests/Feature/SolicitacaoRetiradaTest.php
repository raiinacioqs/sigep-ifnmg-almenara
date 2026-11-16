<?php

namespace Tests\Feature;

use App\Models\BemPatrimonial;
use App\Models\Responsavel;
use App\Models\Categoria;
use App\Models\Estoque;
use App\Models\Material;
use App\Models\Role;
use App\Models\Setor;
use App\Models\User;
use App\Services\SolicitacaoRetiradaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SolicitacaoRetiradaTest extends TestCase
{
    use RefreshDatabase;

    private function criarUsuarioServidor(): User
    {
        $role = Role::create([
            'nome' => 'Servidor',
            'descricao' => 'Perfil utilizado nos testes',
        ]);

        $setor = Setor::create([
            'nome' => 'Setor de Teste',
            'sigla' => 'ST',
            'localizacao' => 'Campus Almenara',
            'status' => 'ativo',
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'setor_id' => $setor->id,
            'status' => 'ativo',
        ]);
    }

    private function criarMaterialComEstoque(
        float $quantidade = 20
    ): Material {
        $categoria = Categoria::create([
            'nome' => 'Materiais de Escritório',
            'descricao' => 'Categoria utilizada nos testes',
            'tipo' => 'consumo',
            'status' => 'ativo',
        ]);

        $material = Material::create([
            'nome' => 'Papel A4',
            'descricao' => 'Papel branco formato A4',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 5,
            'status' => 'ativo',
        ]);

        Estoque::create([
            'material_id' => $material->id,
            'quantidade_atual' => $quantidade,
        ]);

        return $material;
    }

    private function dadosSolicitacao(
        Material $material
    ): array {
        return [
            'data_necessaria' => '2026-12-01',
            'data_limite' => '2026-12-05',
            'justificativa' => 'Reposição de materiais para o setor.',
            'observacao' => 'Solicitação de teste.',
            'itens' => [
                [
                    'material_id' => $material->id,
                    'quantidade_solicitada' => 3,
                    'observacao' => 'Material para expediente.',
                ],
            ],
        ];
    }

    public function test_servidor_consegue_criar_solicitacao(): void
    {
        $usuario = $this->criarUsuarioServidor();
        $material = $this->criarMaterialComEstoque();

        $service = app(SolicitacaoRetiradaService::class);

        $solicitacao = $service->criar(
            $this->dadosSolicitacao($material),
            $usuario
        );

        $this->assertDatabaseHas('solicitacoes_retirada', [
            'id' => $solicitacao->id,
            'usuario_id' => $usuario->id,
            'setor_id' => $usuario->setor_id,
            'status' => 'pendente',
        ]);

        $this->assertDatabaseHas('itens_solicitacao_retirada', [
            'solicitacao_retirada_id' => $solicitacao->id,
            'material_id' => $material->id,
            'quantidade_solicitada' => 3,
        ]);

        $this->assertDatabaseCount('solicitacoes_retirada', 1);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 1);
    }

    public function test_setor_da_solicitacao_e_obtido_do_usuario(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $outroSetor = Setor::create([
            'nome' => 'Outro Setor',
            'sigla' => 'OS',
            'localizacao' => 'Campus Almenara',
            'status' => 'ativo',
        ]);

        $material = $this->criarMaterialComEstoque();

        $dados = $this->dadosSolicitacao($material);

        // Simula tentativa de informar outro setor pelo cliente.
        $dados['setor_id'] = $outroSetor->id;

        $service = app(SolicitacaoRetiradaService::class);

        $solicitacao = $service->criar($dados, $usuario);

        $this->assertEquals(
            $usuario->setor_id,
            $solicitacao->setor_id
        );

        $this->assertDatabaseMissing('solicitacoes_retirada', [
            'id' => $solicitacao->id,
            'setor_id' => $outroSetor->id,
        ]);
    }

    public function test_abertura_da_solicitacao_nao_altera_estoque(): void
    {
        $usuario = $this->criarUsuarioServidor();
        $material = $this->criarMaterialComEstoque(20);

        $service = app(SolicitacaoRetiradaService::class);

        $service->criar(
            $this->dadosSolicitacao($material),
            $usuario
        );

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 20,
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 0);
    }

    public function test_item_invalido_reverte_toda_a_solicitacao(): void
    {
        $usuario = $this->criarUsuarioServidor();
        $material = $this->criarMaterialComEstoque();

        $dados = $this->dadosSolicitacao($material);

        $dados['itens'][] = [
            'material_id' => 999999,
            'quantidade_solicitada' => 2,
        ];

        $service = app(SolicitacaoRetiradaService::class);

        try {
            $service->criar($dados, $usuario);

            $this->fail(
                'Era esperada uma exceção de validação.'
            );
        } catch (ValidationException $erro) {
            $this->assertArrayHasKey(
                'itens.1.material_id',
                $erro->errors()
            );
        }

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 0);

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 20,
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 0);
    }


    public function test_usuario_sem_setor_nao_consegue_criar_solicitacao(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $usuario->update([
            'setor_id' => null,
        ]);

        $material = $this->criarMaterialComEstoque();

        $service = app(SolicitacaoRetiradaService::class);

        try {
            $service->criar(
                $this->dadosSolicitacao($material),
                $usuario->fresh()
            );

            $this->fail(
                'Era esperada uma exceção de validação.'
            );
        } catch (ValidationException $erro) {
            $this->assertArrayHasKey(
                'setor_id',
                $erro->errors()
            );
        }

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 0);
    }

    public function test_material_de_categoria_patrimonial_nao_pode_ser_solicitado_como_consumo(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $categoria = Categoria::create([
            'nome' => 'Equipamentos',
            'tipo' => 'patrimonio',
            'status' => 'ativo',
        ]);

        $material = Material::create([
            'nome' => 'Computador',
            'descricao' => 'Computador para teste',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'unidade',
            'quantidade_minima' => 1,
            'status' => 'ativo',
        ]);

        $dados = $this->dadosSolicitacao($material);

        $service = app(SolicitacaoRetiradaService::class);

        try {
            $service->criar($dados, $usuario);

            $this->fail('Era esperada uma exceção de validação.');
        } catch (ValidationException $erro) {
            $this->assertArrayHasKey(
                'itens.0.material_id',
                $erro->errors()
            );
        }

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 0);
    }

    public function test_abertura_da_solicitacao_nao_altera_bem_patrimonial(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $categoria = Categoria::create([
            'nome' => 'Equipamentos',
            'tipo' => 'patrimonio',
            'status' => 'ativo',
        ]);

        $responsavel = Responsavel::create([
            'siape' => '123456',
            'nome' => 'Responsável de Teste',
            'email' => 'responsavel.teste@local.test',
            'cargo' => 'Técnico',
            'setor_id' => $usuario->setor_id,
            'status' => 'ativo',
        ]);

        $bem = BemPatrimonial::create([
            'numero_tombo' => 'TOMBO-TESTE-001',
            'descricao' => 'Computador de teste',
            'categoria_id' => $categoria->id,
            'setor_id' => $usuario->setor_id,
            'responsavel_id' => $responsavel->id,
            'status' => 'ativo',
        ]);

        $dados = [
            'data_necessaria' => '2026-12-01',
            'data_limite' => '2026-12-05',
            'justificativa' => 'Solicitação de equipamento para teste.',
            'observacao' => null,
            'itens' => [
                [
                    'bem_patrimonial_id' => $bem->id,
                    'quantidade_solicitada' => 1,
                    'observacao' => null,
                ],
            ],
        ];

        $service = app(SolicitacaoRetiradaService::class);

        $solicitacao = $service->criar($dados, $usuario);

        $this->assertDatabaseHas('itens_solicitacao_retirada', [
            'solicitacao_retirada_id' => $solicitacao->id,
            'bem_patrimonial_id' => $bem->id,
            'material_id' => null,
        ]);

        $this->assertDatabaseHas('bens_patrimoniais', [
            'id' => $bem->id,
            'setor_id' => $usuario->setor_id,
            'responsavel_id' => $responsavel->id,
        ]);
    }

    public function test_servidor_consegue_abrir_solicitacao_pela_api(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token);

        $material = $this->criarMaterialComEstoque();

        $resposta = $this->postJson(
            '/api/servidor/solicitacoes-retirada',
            $this->dadosSolicitacao($material)
        );

        $resposta
            ->assertCreated()
            ->assertJsonPath('data.usuario_id', $usuario->id)
            ->assertJsonPath('data.setor_id', $usuario->setor_id)
            ->assertJsonPath('data.status', 'pendente')
            ->assertJsonPath('data.itens.0.material_id', $material->id);

        $this->assertDatabaseCount('solicitacoes_retirada', 1);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 1);
    }

    public function test_usuario_nao_autenticado_nao_pode_abrir_solicitacao_pela_api(): void
    {
        $material = $this->criarMaterialComEstoque();

        $this->postJson(
            '/api/servidor/solicitacoes-retirada',
            $this->dadosSolicitacao($material)
        )->assertUnauthorized();

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
    }

    public function test_administrador_nao_pode_abrir_solicitacao_pela_rota_de_servidor(): void
    {
        $role = Role::create([
            'nome' => 'Administrador',
            'descricao' => 'Perfil utilizado no teste',
        ]);

        $usuario = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'ativo',
        ]);

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token);

        $material = $this->criarMaterialComEstoque();

        $this->postJson(
            '/api/servidor/solicitacoes-retirada',
            $this->dadosSolicitacao($material)
        )->assertForbidden();

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
    }

    public function test_api_nao_permite_item_sem_material_ou_bem_patrimonial(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token);

        $material = $this->criarMaterialComEstoque();

        $dados = $this->dadosSolicitacao($material);

        unset($dados['itens'][0]['material_id']);

        $this->postJson(
            '/api/servidor/solicitacoes-retirada',
            $dados
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'itens.0.material_id',
                'itens.0.bem_patrimonial_id',
            ]);

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 0);
    }

   public function test_api_nao_permite_material_e_bem_patrimonial_no_mesmo_item(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token);

        $material = $this->criarMaterialComEstoque();

        $categoria = Categoria::create([
            'nome' => 'Equipamentos',
            'tipo' => 'patrimonio',
            'status' => 'ativo',
        ]);

        $bem = BemPatrimonial::create([
            'numero_tombo' => 'TOMBO-TESTE-002',
            'descricao' => 'Computador de teste',
            'categoria_id' => $categoria->id,
            'setor_id' => $usuario->setor_id,
            'status' => 'ativo',
        ]);

        $dados = $this->dadosSolicitacao($material);

        $dados['itens'][0]['bem_patrimonial_id'] = $bem->id;

        $dados['itens'][0]['quantidade_solicitada'] = 1;

        $this->postJson(
            '/api/servidor/solicitacoes-retirada',
            $dados
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens.0');

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 0);
    }

    public function test_api_nao_permite_quantidade_maior_que_um_para_bem_patrimonial(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $token = $usuario->createToken('teste')->plainTextToken;

        $this->withToken($token);

        $categoria = Categoria::create([
            'nome' => 'Equipamentos',
            'tipo' => 'patrimonio',
            'status' => 'ativo',
        ]);

        $bem = BemPatrimonial::create([
            'numero_tombo' => 'TOMBO-TESTE-003',
            'descricao' => 'Computador de teste',
            'categoria_id' => $categoria->id,
            'setor_id' => $usuario->setor_id,
            'status' => 'ativo',
        ]);

        $dados = [
            'data_necessaria' => '2026-12-01',
            'data_limite' => '2026-12-05',
            'justificativa' => 'Solicitação de equipamento.',
            'observacao' => null,
            'itens' => [
                [
                    'bem_patrimonial_id' => $bem->id,
                    'quantidade_solicitada' => 2,
                    'observacao' => null,
                ],
            ],
        ];

        $this->postJson(
            '/api/servidor/solicitacoes-retirada',
            $dados
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'itens.0.quantidade_solicitada'
            );

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 0);
    }

    public function test_service_nao_permite_quantidade_maior_que_um_para_bem_patrimonial(): void
    {
        $usuario = $this->criarUsuarioServidor();

        $categoria = Categoria::create([
            'nome' => 'Equipamentos',
            'tipo' => 'patrimonio',
            'status' => 'ativo',
        ]);

        $bem = BemPatrimonial::create([
            'numero_tombo' => 'TOMBO-TESTE-004',
            'descricao' => 'Computador de teste',
            'categoria_id' => $categoria->id,
            'setor_id' => $usuario->setor_id,
            'status' => 'ativo',
        ]);

        $dados = [
            'data_necessaria' => '2026-12-01',
            'data_limite' => '2026-12-05',
            'justificativa' => 'Solicitação de equipamento.',
            'observacao' => null,
            'itens' => [
                [
                    'bem_patrimonial_id' => $bem->id,
                    'quantidade_solicitada' => 2,
                    'observacao' => null,
                ],
            ],
        ];

        $service = app(SolicitacaoRetiradaService::class);

        try {
            $service->criar($dados, $usuario);

            $this->fail('Era esperada uma exceção de validação.');
        } catch (ValidationException $erro) {
            $this->assertArrayHasKey(
                'itens.0.quantidade_solicitada',
                $erro->errors()
            );
        }

        $this->assertDatabaseCount('solicitacoes_retirada', 0);
        $this->assertDatabaseCount('itens_solicitacao_retirada', 0);
    }
}