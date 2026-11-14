<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EntradaEstoque;
use App\Models\Estoque;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\MovimentacaoEstoque;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EntradaEstoqueTest extends TestCase
{
    use RefreshDatabase;

    private function autenticarComo(string $perfil = 'Administrador'): User
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

        return $usuario;
    }

    private function criarFornecedor(array $sobrescrever = []): Fornecedor
    {
        return Fornecedor::create(array_merge([
            'razao_social' => 'Fornecedor de Teste LTDA',
            'nome_fantasia' => 'Fornecedor Teste',
            'cnpj' => '11222333000181',
            'email' => 'fornecedor@teste.com',
            'telefone' => '33999999999',
            'endereco' => 'Almenara - MG',
            'status' => 'ativo',
        ], $sobrescrever));
    }

    private function criarMaterial(array $sobrescrever = []): Material
    {
        $categoria = Categoria::create([
            'nome' => 'Materiais de Escritório',
            'descricao' => 'Categoria utilizada nos testes',
            'tipo' => 'consumo',
            'status' => 'ativo',
        ]);

        $material = Material::create(array_merge([
            'nome' => 'Papel A4',
            'descricao' => 'Papel branco formato A4',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'Resma',
            'quantidade_minima' => 15,
            'status' => 'ativo',
        ], $sobrescrever));

        Estoque::create([
            'material_id' => $material->id,
            'quantidade_atual' => 0,
        ]);

        return $material;
    }

    
    private function dadosEntrada(
        Material $material,
        array $sobrescrever = []
    ): array {
        $fornecedorId = array_key_exists('fornecedor_id', $sobrescrever)
            ? $sobrescrever['fornecedor_id']
            : $this->criarFornecedor()->id;

        return array_merge([
            'fornecedor_id' => $fornecedorId,
            'numero_nota_fiscal' => 'NF-2026-001',
            'data_entrada' => '2026-11-14',
            'observacao' => 'Entrada para teste',
            'itens' => [
                [
                    'material_id' => $material->id,
                    'quantidade' => 10,
                    'valor_unitario' => 25.50,
                ],
            ],
        ], $sobrescrever);
    }


    public function test_administrador_consegue_registrar_entrada_de_estoque(): void
    {
        $usuario = $this->autenticarComo();

        $material = $this->criarMaterial();

        $this->postJson(
            '/api/entradas-estoque',
            $this->dadosEntrada($material)
        )
            ->assertCreated()
            ->assertJsonPath('data.numero_nota_fiscal', 'NF-2026-001');

        $this->assertDatabaseCount('entradas_estoque', 1);
        $this->assertDatabaseCount('itens_entrada_estoque', 1);

        $this->assertDatabaseHas('entradas_estoque', [
            'usuario_id' => $usuario->id,
            'numero_nota_fiscal' => 'NF-2026-001',
        ]);
    }

    public function test_gestor_consegue_registrar_entrada_de_estoque(): void
    {
        $this->autenticarComo('Gestor de Almoxarifado');

        $material = $this->criarMaterial();

        $this->postJson(
            '/api/entradas-estoque',
            $this->dadosEntrada($material)
        )
            ->assertCreated();

        $this->assertDatabaseCount('entradas_estoque', 1);
    }

    public function test_entrada_atualiza_quantidade_do_estoque(): void
    {
        $this->autenticarComo();

        $material = $this->criarMaterial();

        $this->postJson(
            '/api/entradas-estoque',
            $this->dadosEntrada($material)
        )->assertCreated();

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 10,
        ]);
    }

    public function test_entrada_registra_movimentacao_de_estoque(): void
    {
        $usuario = $this->autenticarComo();

        $material = $this->criarMaterial();

        $this->postJson(
            '/api/entradas-estoque',
            $this->dadosEntrada($material)
        )->assertCreated();

        $this->assertDatabaseHas('movimentacoes_estoque', [
            'material_id' => $material->id,
            'usuario_id' => $usuario->id,
            'tipo' => 'entrada',
            'quantidade' => 10,
        ]);

        $this->assertDatabaseCount('movimentacoes_estoque', 1);
    }

    public function test_servidor_nao_consegue_registrar_entrada(): void
    {
        $this->autenticarComo('Servidor');

        $material = $this->criarMaterial();

        $this->postJson(
            '/api/entradas-estoque',
            $this->dadosEntrada($material)
        )->assertForbidden();

        $this->assertDatabaseCount('entradas_estoque', 0);
        $this->assertDatabaseCount('movimentacoes_estoque', 0);

        $this->assertDatabaseHas('estoques', [
            'material_id' => $material->id,
            'quantidade_atual' => 0,
        ]);
    }


    public function test_nao_permite_entrada_sem_itens(): void
    {
        $this->autenticarComo();

        $material = $this->criarMaterial();

        $dados = $this->dadosEntrada($material, [
            'itens' => [],
        ]);

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens');

        $this->assertDatabaseCount('entradas_estoque', 0);
    }

    public function test_nao_permite_quantidade_zero(): void
    {
        $this->autenticarComo();

        $material = $this->criarMaterial();

        $dados = $this->dadosEntrada($material);
        $dados['itens'][0]['quantidade'] = 0;

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens.0.quantidade');

        $this->assertDatabaseCount('entradas_estoque', 0);
    }

    public function test_nao_permite_quantidade_negativa(): void
    {
        $this->autenticarComo();

        $material = $this->criarMaterial();

        $dados = $this->dadosEntrada($material);
        $dados['itens'][0]['quantidade'] = -5;

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens.0.quantidade');

        $this->assertDatabaseCount('entradas_estoque', 0);
    }

    public function test_nao_permite_fornecedor_inativo(): void
    {
        $this->autenticarComo();

        $material = $this->criarMaterial();

        $fornecedor = $this->criarFornecedor([
            'status' => 'inativo',
        ]);

        $dados = $this->dadosEntrada($material, [
            'fornecedor_id' => $fornecedor->id,
        ]);

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fornecedor_id');

        $this->assertDatabaseCount('entradas_estoque', 0);
    }

    public function test_nao_permite_material_inativo(): void
    {
        $this->autenticarComo();

        $material = $this->criarMaterial([
            'status' => 'inativo',
        ]);

        $this->postJson(
            '/api/entradas-estoque',
            $this->dadosEntrada($material)
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens.0.material_id');

        $this->assertDatabaseCount('entradas_estoque', 0);
    }

    public function test_nao_permite_material_de_categoria_inativa(): void
    {
        $this->autenticarComo();

        $material = $this->criarMaterial();

        $material->categoria->update([
            'status' => 'inativo',
        ]);

        $this->postJson(
            '/api/entradas-estoque',
            $this->dadosEntrada($material)
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens');

        $this->assertDatabaseCount('entradas_estoque', 0);
    }

    public function test_nao_permite_repetir_material_na_mesma_entrada(): void
    {
        $this->autenticarComo();

        $material = $this->criarMaterial();

        $dados = $this->dadosEntrada($material);

        $dados['itens'][] = $dados['itens'][0];

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens.1.material_id');

        $this->assertDatabaseCount('entradas_estoque', 0);
    }

    public function test_admin_pode_cadastrar_entrada_com_nota_fiscal_pdf(): void
    {
        Storage::fake('local');

        $usuario = $this->autenticarComo('Administrador');
        $material = $this->criarMaterial();

        $arquivo = UploadedFile::fake()->create(
            'nota-fiscal.pdf',
            100,
            'application/pdf'
        );

        $dados = $this->dadosEntrada($material, [
            'arquivo_nota_fiscal' => $arquivo,
        ]);

        $resposta = $this->postJson('/api/entradas-estoque', $dados);

        $resposta
            ->assertCreated()
            ->assertJsonPath('data.possui_nota_fiscal', true)
            ->assertJsonPath(
                'data.url_nota_fiscal',
                url('/api/entradas-estoque/1/nota-fiscal')
            );

        $entrada = EntradaEstoque::firstOrFail();

        $this->assertNotNull($entrada->arquivo_nota_fiscal);

        Storage::disk('local')->assertExists(
            $entrada->arquivo_nota_fiscal
        );
    }

    public function test_usuario_autenticado_pode_baixar_nota_fiscal(): void
    {
        Storage::fake('local');

        $usuario = $this->autenticarComo('Administrador');
        $material = $this->criarMaterial();

        $arquivo = UploadedFile::fake()->create(
            'nota-fiscal.pdf',
            100,
            'application/pdf'
        );

        $dados = $this->dadosEntrada($material, [
            'arquivo_nota_fiscal' => $arquivo,
        ]);

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertCreated();

        $entrada = EntradaEstoque::firstOrFail();

        $resposta = $this->get(
            "/api/entradas-estoque/{$entrada->id}/nota-fiscal"
        );

        $resposta->assertOk();

        $this->assertStringContainsString(
            'application/pdf',
            $resposta->headers->get('Content-Type')
        );

        $this->assertStringContainsString(
            "nota-fiscal-entrada-{$entrada->id}.pdf",
            $resposta->headers->get('Content-Disposition')
        );
    }

    public function test_usuario_nao_autenticado_nao_pode_baixar_nota_fiscal(): void
    {
        Storage::fake('local');

        $usuario = $this->autenticarComo('Administrador');
        $material = $this->criarMaterial();

        $arquivo = UploadedFile::fake()->create(
            'nota-fiscal.pdf',
            100,
            'application/pdf'
        );

        $dados = $this->dadosEntrada($material, [
            'arquivo_nota_fiscal' => $arquivo,
        ]);

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertCreated();

        $entrada = EntradaEstoque::firstOrFail();

        // Remove os cabeçalhos e limpa os guards de autenticação.
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $this->getJson(
            "/api/entradas-estoque/{$entrada->id}/nota-fiscal"
        )->assertUnauthorized();
    }

    public function test_retorna_404_quando_entrada_nao_possui_nota_fiscal(): void
    {
        Storage::fake('local');

        $this->autenticarComo('Administrador');
        $material = $this->criarMaterial();

        $dados = $this->dadosEntrada($material);

        $resposta = $this->postJson('/api/entradas-estoque', $dados);

        $resposta->assertCreated();

        $entrada = EntradaEstoque::firstOrFail();

        $this->getJson(
            "/api/entradas-estoque/{$entrada->id}/nota-fiscal"
        )->assertNotFound();
    }

    public function test_retorna_404_quando_arquivo_nao_existe_no_armazenamento(): void
    {
        Storage::fake('local');

        $this->autenticarComo('Administrador');
        $material = $this->criarMaterial();

        $dados = $this->dadosEntrada($material);

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertCreated();

        $entrada = EntradaEstoque::firstOrFail();

        $entrada->update([
            'arquivo_nota_fiscal' => 'notas-fiscais/arquivo-inexistente.pdf',
        ]);

        $this->getJson(
            "/api/entradas-estoque/{$entrada->id}/nota-fiscal"
        )->assertNotFound();
    }

    public function test_remove_pdf_e_reverte_cadastro_quando_estoque_nao_existe(): void
    {
        Storage::fake('local');

        $this->autenticarComo('Administrador');

        $categoria = Categoria::create([
            'nome' => 'Categoria sem estoque',
            'descricao' => 'Categoria utilizada no teste',
            'tipo' => 'consumo',
            'status' => 'ativo',
        ]);

        // Cria material ativo, mas sem registro correspondente em estoques.
        $material = Material::create([
            'nome' => 'Material sem estoque',
            'descricao' => 'Material utilizado para provocar falha',
            'categoria_id' => $categoria->id,
            'unidade_medida' => 'Unidade',
            'quantidade_minima' => 5,
            'status' => 'ativo',
        ]);

        $arquivo = UploadedFile::fake()->create(
            'nota-fiscal.pdf',
            100,
            'application/pdf'
        );

        $dados = $this->dadosEntrada($material, [
            'arquivo_nota_fiscal' => $arquivo,
        ]);

        $resposta = $this->postJson('/api/entradas-estoque', $dados);

        $resposta->assertUnprocessable();

        $this->assertDatabaseCount('entradas_estoque', 0);
        $this->assertDatabaseCount('itens_entrada_estoque', 0);
        $this->assertDatabaseCount('movimentacoes_estoque', 0);

        $this->assertDatabaseMissing('estoques', [
            'material_id' => $material->id,
        ]);

        $this->assertCount(
            0,
            Storage::disk('local')->allFiles('notas-fiscais')
        );
    }

    public function test_servidor_nao_pode_consultar_entradas_de_estoque(): void
    {
        $this->autenticarComo('Servidor');

        $this->getJson('/api/entradas-estoque')
            ->assertForbidden();
    }

    public function test_servidor_nao_pode_consultar_entrada_especifica(): void
    {
        $this->autenticarComo('Administrador');

        $material = $this->criarMaterial();

        $resposta = $this->postJson(
            '/api/entradas-estoque',
            $this->dadosEntrada($material)
        );

        $resposta->assertCreated();

        $entrada = EntradaEstoque::firstOrFail();

        $this->autenticarComo('Servidor');

        // Limpa o guard anterior para aplicar a nova autenticação.
        $this->app['auth']->forgetGuards();

        $this->getJson("/api/entradas-estoque/{$entrada->id}")
            ->assertForbidden();
    }

    public function test_servidor_nao_pode_baixar_nota_fiscal(): void
    {
        Storage::fake('local');

        $this->autenticarComo('Administrador');

        $material = $this->criarMaterial();

        $arquivo = UploadedFile::fake()->create(
            'nota-fiscal.pdf',
            100,
            'application/pdf'
        );

        $dados = $this->dadosEntrada($material, [
            'arquivo_nota_fiscal' => $arquivo,
        ]);

        $this->postJson('/api/entradas-estoque', $dados)
            ->assertCreated();

        $entrada = EntradaEstoque::firstOrFail();

        $this->autenticarComo('Servidor');
        $this->app['auth']->forgetGuards();

        $this->getJson(
            "/api/entradas-estoque/{$entrada->id}/nota-fiscal"
        )->assertForbidden();
    }

    public function test_gestor_pode_consultar_entradas_de_estoque(): void
    {
        $this->autenticarComo('Gestor de Almoxarifado');

        $this->getJson('/api/entradas-estoque')
            ->assertOk();
    }

    public function test_administrador_pode_consultar_entradas_de_estoque(): void
    {
        $this->autenticarComo('Administrador');

        $this->getJson('/api/entradas-estoque')
            ->assertOk();
    }
}
