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

    private function criarUsuarioGestor(): User
    {
        $role = Role::create([
            'nome' => 'Gestor de Almoxarifado',
            'descricao' => 'Perfil utilizado nos testes',
        ]);

        $setor = Setor::create([
            'nome' => 'Setor de Gestão',
            'sigla' => 'SG',
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
    float $quantidade = 20,
    string $nome = 'Papel A4'
    ): Material {
        $categoria = Categoria::firstOrCreate(
            [
                'nome' => 'Materiais de Escritório',
                'tipo' => 'consumo',
            ],
            [
                'descricao' => 'Categoria utilizada nos testes',
                'status' => 'ativo',
            ]
        );

        $material = Material::create([
            'nome' => $nome,
            'descricao' => 'Material utilizado nos testes',
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

    public function test_gestor_consegue_listar_solicitacoes_pela_api(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $material = $this->criarMaterialComEstoque();

        $service = app(SolicitacaoRetiradaService::class);

        $solicitacao = $service->criar(
            $this->dadosSolicitacao($material),
            $servidor
        );

        $gestor = $this->criarUsuarioGestor();

        $token = $gestor->createToken('teste')->plainTextToken;

        $resposta = $this->withToken($token)
            ->getJson('/api/gestao/solicitacoes-retirada');

        $resposta
            ->assertOk()
            ->assertJsonPath('data.0.id', $solicitacao->id)
            ->assertJsonPath('data.0.status', 'pendente')
            ->assertJsonPath('data.0.usuario_id', $servidor->id)
            ->assertJsonPath('meta.per_page', 10);
    }

    public function test_gestor_consegue_filtrar_solicitacoes_por_status(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $material = $this->criarMaterialComEstoque();

        $service = app(SolicitacaoRetiradaService::class);

        $solicitacao = $service->criar(
            $this->dadosSolicitacao($material),
            $servidor
        );

        $gestor = $this->criarUsuarioGestor();

        $token = $gestor->createToken('teste')->plainTextToken;

        $resposta = $this->withToken($token)
            ->getJson('/api/gestao/solicitacoes-retirada?status=pendente');

        $resposta
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $solicitacao->id)
            ->assertJsonPath('data.0.status', 'pendente');
    }

    public function test_gestor_consegue_consultar_solicitacao_especifica(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $material = $this->criarMaterialComEstoque();

        $service = app(SolicitacaoRetiradaService::class);

        $solicitacao = $service->criar(
            $this->dadosSolicitacao($material),
            $servidor
        );

        $gestor = $this->criarUsuarioGestor();

        $token = $gestor->createToken('teste')->plainTextToken;

        $resposta = $this->withToken($token)
            ->getJson(
                "/api/gestao/solicitacoes-retirada/{$solicitacao->id}"
            );

        $resposta
            ->assertOk()
            ->assertJsonPath('data.id', $solicitacao->id)
            ->assertJsonPath('data.status', 'pendente')
            ->assertJsonPath('data.usuario_id', $servidor->id)
            ->assertJsonPath('data.itens.0.material_id', $material->id);
    }

    public function test_consulta_de_solicitacao_inexistente_retorna_404(): void
    {
        $gestor = $this->criarUsuarioGestor();

        $token = $gestor->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/gestao/solicitacoes-retirada/999999')
            ->assertNotFound();
    }

    public function test_servidor_nao_consegue_acessar_listagem_de_solicitacoes_da_gestao(): void
    {
        $servidor = $this->criarUsuarioServidor();

        $token = $servidor->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/gestao/solicitacoes-retirada')
            ->assertForbidden();
    }


    public function test_gestor_atende_solicitacao_e_registra_saida_no_estoque(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $gestor = $this->criarUsuarioGestor();
        $material = $this->criarMaterialComEstoque(20);

        $service = app(SolicitacaoRetiradaService::class);

        $solicitacao = $service->criar(
            $this->dadosSolicitacao($material),
            $servidor
        );

        $token = $gestor->createToken('teste')->plainTextToken;

        $resposta = $this->withToken($token)
            ->postJson(
                "/api/gestao/solicitacoes-retirada/{$solicitacao->id}/atender"
            );

        $resposta
            ->assertOk()
            ->assertJsonPath('data.id', $solicitacao->id)
            ->assertJsonPath('data.status', 'atendida')
            ->assertJsonPath('data.analisado_por', $gestor->id);

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 17,
        ]);

        $this->assertDatabaseHas('movimentacoes_estoque', [
            'material_id' => $material->id,
            'usuario_id' => $gestor->id,
            'solicitacao_retirada_id' => $solicitacao->id,
            'tipo' => 'saida',
            'quantidade' => 3,
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 1);
    }


    public function test_gestor_atende_solicitacao_e_transfere_bem_patrimonial(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $gestor = $this->criarUsuarioGestor();

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
            'setor_id' => $servidor->setor_id,
            'status' => 'ativo',
        ]);

            $setorOrigem = Setor::create([
            'nome' => 'Setor de Origem',
            'sigla' => 'SO',
            'localizacao' => 'Campus Almenara',
            'status' => 'ativo',
        ]);

        $bem = BemPatrimonial::create([
            'numero_tombo' => 'TOMBO-ATENDIMENTO-001',
            'descricao' => 'Computador para transferência',
            'categoria_id' => $categoria->id,
            'setor_id' => $setorOrigem->id,
            'responsavel_id' => $responsavel->id,
            'status' => 'ativo',
        ]);

        $dados = [
            'data_necessaria' => '2026-12-01',
            'data_limite' => '2026-12-05',
            'justificativa' => 'Transferência de equipamento.',
            'observacao' => null,
            'itens' => [
                [
                    'bem_patrimonial_id' => $bem->id,
                    'quantidade_solicitada' => 1,
                    'observacao' => null,
                ],
            ],
        ];

        $solicitacao = app(SolicitacaoRetiradaService::class)
            ->criar($dados, $servidor);

        $token = $gestor->createToken('teste')->plainTextToken;

        $resposta = $this->withToken($token)
            ->postJson(
                "/api/gestao/solicitacoes-retirada/{$solicitacao->id}/atender"
            );

        $resposta
            ->assertOk()
            ->assertJsonPath('data.status', 'atendida');

        $this->assertDatabaseHas('bens_patrimoniais', [
            'id' => $bem->id,
            'setor_id' => $servidor->setor_id,
            'responsavel_id' => $responsavel->id,
        ]);

        $this->assertDatabaseHas('movimentacoes_patrimoniais', [
            'bem_patrimonial_id' => $bem->id,
            'solicitacao_retirada_id' => $solicitacao->id,
            'setor_origem_id' => $setorOrigem->id,
            'setor_destino_id' => $servidor->setor_id,
            'responsavel_origem_id' => $responsavel->id,
            'responsavel_destino_id' => null,
            'usuario_id' => $gestor->id,
            'tipo' => 'transferencia',
        ]);
    }


    public function test_atendimento_com_estoque_insuficiente_nao_altera_dados(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $gestor = $this->criarUsuarioGestor();
        $material = $this->criarMaterialComEstoque(2);

        $solicitacao = app(SolicitacaoRetiradaService::class)->criar(
            $this->dadosSolicitacao($material),
            $servidor
        );

        $token = $gestor->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->postJson(
                "/api/gestao/solicitacoes-retirada/{$solicitacao->id}/atender"
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estoque');

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 2,
        ]);

        $this->assertDatabaseHas('solicitacoes_retirada', [
            'id' => $solicitacao->id,
            'status' => 'pendente',
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 0);
    }

    public function test_solicitacao_nao_pode_ser_atendida_duas_vezes(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $gestor = $this->criarUsuarioGestor();
        $material = $this->criarMaterialComEstoque(20);

        $solicitacao = app(SolicitacaoRetiradaService::class)->criar(
            $this->dadosSolicitacao($material),
            $servidor
        );

        $token = $gestor->createToken('teste')->plainTextToken;

        $url = "/api/gestao/solicitacoes-retirada/{$solicitacao->id}/atender";

        $this->withToken($token)
            ->postJson($url)
            ->assertOk()
            ->assertJsonPath('data.status', 'atendida');

        $this->withToken($token)
            ->postJson($url)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 17,
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 1);

        $this->assertDatabaseHas('solicitacoes_retirada', [
            'id' => $solicitacao->id,
            'status' => 'atendida',
        ]);
    }

    public function test_servidor_nao_pode_atender_solicitacao(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $material = $this->criarMaterialComEstoque(20);

        $solicitacao = app(SolicitacaoRetiradaService::class)->criar(
            $this->dadosSolicitacao($material),
            $servidor
        );

        $token = $servidor->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->postJson(
                "/api/gestao/solicitacoes-retirada/{$solicitacao->id}/atender"
            )
            ->assertForbidden();

        $this->assertDatabaseHas('solicitacoes_retirada', [
            'id' => $solicitacao->id,
            'status' => 'pendente',
        ]);

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 20,
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 0);
    }

    public function test_administrador_nao_pode_atender_solicitacao(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $material = $this->criarMaterialComEstoque(20);

        $solicitacao = app(SolicitacaoRetiradaService::class)->criar(
            $this->dadosSolicitacao($material),
            $servidor
        );

        $role = Role::create([
            'nome' => 'Administrador',
            'descricao' => 'Administrador do sistema',
        ]);

        $setor = Setor::create([
            'nome' => 'Setor Administrativo',
            'sigla' => 'SA',
            'status' => 'ativo',
        ]);

        $administrador = User::factory()->create([
            'role_id' => $role->id,
            'setor_id' => $setor->id,
            'status' => 'ativo',
        ]);

        $token = $administrador->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->postJson(
                "/api/gestao/solicitacoes-retirada/{$solicitacao->id}/atender"
            )
            ->assertForbidden();

        $this->assertDatabaseHas('solicitacoes_retirada', [
            'id' => $solicitacao->id,
            'status' => 'pendente',
        ]);

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 20,
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 0);
    }

    public function test_atender_solicitacao_inexistente_retorna_404(): void
    {
        $gestor = $this->criarUsuarioGestor();

        $token = $gestor->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/gestao/solicitacoes-retirada/99999/atender')
            ->assertNotFound();
    }

    public function test_atendimento_com_varios_itens_e_estoque_insuficiente_reverte_toda_operacao(): void
    {
        $servidor = $this->criarUsuarioServidor();
        $gestor = $this->criarUsuarioGestor();

        $material1 = $this->criarMaterialComEstoque(20, 'Papel A4');
        $material2 = $this->criarMaterialComEstoque(2, 'Papel A3');

        $dados = [
            'data_necessaria' => now()->addDays(2)->toDateString(),
            'justificativa' => 'Teste de atomicidade',
            'itens' => [
                [
                    'material_id' => $material1->id,
                    'quantidade_solicitada' => 3,
                ],
                [
                    'material_id' => $material2->id,
                    'quantidade_solicitada' => 3,
                ],
            ],
        ];

        $solicitacao = app(SolicitacaoRetiradaService::class)->criar(
            $dados,
            $servidor
        );

        $token = $gestor->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->postJson(
                "/api/gestao/solicitacoes-retirada/{$solicitacao->id}/atender"
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('estoque');

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material1->id,
            'quantidade_atual' => 20,
        ]);

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material2->id,
            'quantidade_atual' => 2,
        ]);

        $this->assertDatabaseHas('solicitacoes_retirada', [
            'id' => $solicitacao->id,
            'status' => 'pendente',
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 0);
    }
}