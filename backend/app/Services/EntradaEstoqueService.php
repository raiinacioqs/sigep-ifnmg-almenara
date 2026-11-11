<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\EntradaEstoque;
use App\Models\Estoque;
use App\Models\Fornecedor;
use App\Models\Material;
use App\Models\MovimentacaoEstoque;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Throwable;

class EntradaEstoqueService
{
    /**
     * Registra uma entrada e atualiza os estoques.
     */
    public function cadastrar(array $dados, int $usuarioId): EntradaEstoque
    {
        $arquivo = $dados['arquivo_nota_fiscal'] ?? null;

        unset($dados['arquivo_nota_fiscal']);

        $caminhoArquivo = null;

        try {
            // Armazena o PDF uma única vez, fora da transação.
            if ($arquivo instanceof UploadedFile) {
                $caminhoArquivo = $arquivo->store(
                    'notas-fiscais',
                    'local'
                );

                if (!$caminhoArquivo) {
                    throw new \RuntimeException(
                        'Não foi possível armazenar a nota fiscal.'
                    );
                }
            }

            return DB::transaction(function () use (
                $dados,
                $usuarioId,
                $caminhoArquivo
            ) {
                // Verifica e bloqueia o fornecedor.
                if (!empty($dados['fornecedor_id'])) {
                    $fornecedor = Fornecedor::query()
                        ->lockForUpdate()
                        ->find($dados['fornecedor_id']);

                    if (!$fornecedor || $fornecedor->status !== 'ativo') {
                        throw ValidationException::withMessages([
                            'fornecedor_id' =>
                                'O fornecedor não existe ou está inativo.',
                        ]);
                    }
                }

                // Organiza os itens pelo ID do material.
                $itens = collect($dados['itens'])
                    ->sortBy('material_id')
                    ->values();

                $materialIds = $itens
                    ->pluck('material_id')
                    ->unique()
                    ->values();

                // Consulta e bloqueia os materiais.
                $materiais = Material::query()
                    ->whereIn('id', $materialIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($materiais->count() !== $materialIds->count()) {
                    throw ValidationException::withMessages([
                        'itens' => 'Um ou mais materiais não foram encontrados.',
                    ]);
                }

                // Verifica se todos os materiais estão ativos.
                foreach ($materiais as $material) {
                    if ($material->status !== 'ativo') {
                        throw ValidationException::withMessages([
                            'itens' => 'Um dos materiais está inativo.',
                        ]);
                    }
                }

                // Bloqueia e verifica as categorias.
                $categoriaIds = $materiais
                    ->pluck('categoria_id')
                    ->unique()
                    ->values();

                $categorias = Categoria::query()
                    ->whereIn('id', $categoriaIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($materiais as $material) {
                    $categoria = $categorias->get($material->categoria_id);

                    if (
                        !$categoria ||
                        $categoria->tipo !== 'consumo' ||
                        $categoria->status !== 'ativo'
                    ) {
                        throw ValidationException::withMessages([
                            'itens' =>
                                'Todos os materiais devem pertencer a categorias de consumo ativas.',
                        ]);
                    }
                }

                // Bloqueia todos os estoques uma única vez.
                $estoques = Estoque::query()
                    ->whereIn('material_id', $materialIds)
                    ->orderBy('material_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('material_id');

                if ($estoques->count() !== $materialIds->count()) {
                    throw ValidationException::withMessages([
                        'itens' =>
                            'Um ou mais materiais não possuem registro de estoque.',
                    ]);
                }

                // Cria o cabeçalho da entrada.
                $entrada = EntradaEstoque::create([
                    'fornecedor_id' => $dados['fornecedor_id'] ?? null,
                    'usuario_id' => $usuarioId,
                    'numero_nota_fiscal' =>
                        $dados['numero_nota_fiscal'] ?? null,
                    'arquivo_nota_fiscal' => $caminhoArquivo,
                    'data_entrada' => $dados['data_entrada'],
                    'observacao' => $dados['observacao'] ?? null,
                ]);

                // Processa cada item da entrada.
                foreach ($itens as $item) {
                    $material = $materiais->get($item['material_id']);
                    $estoque = $estoques->get($material->id);

                    $quantidade = $item['quantidade'];

                    // Registra o item recebido.
                    $entrada->itens()->create([
                        'material_id' => $material->id,
                        'quantidade' => $quantidade,
                        'valor_unitario' =>
                            $item['valor_unitario'] ?? null,
                    ]);

                    // Atualiza a quantidade no banco de dados.
                    $estoque->increment(
                        'quantidade_atual',
                        $quantidade
                    );

                    // Registra a movimentação de entrada.
                    MovimentacaoEstoque::create([
                        'material_id' => $material->id,
                        'usuario_id' => $usuarioId,
                        'solicitacao_retirada_id' => null,
                        'tipo' => 'entrada',
                        'quantidade' => $quantidade,
                        'data_movimentacao' => now(),
                        'observacao' =>
                            "Entrada de estoque #{$entrada->id}" .
                            (
                                $entrada->numero_nota_fiscal
                                    ? " - NF {$entrada->numero_nota_fiscal}"
                                    : ''
                            ),
                    ]);
                }

                return $entrada->load([
                    'fornecedor',
                    'usuario',
                    'itens.material.categoria',
                ]);
            }, 3);
        } catch (Throwable $erro) {
            // Remove o PDF caso a operação no banco falhe.
            if ($caminhoArquivo) {
                Storage::disk('local')->delete($caminhoArquivo);
            }

            throw $erro;
        }
    }


    /**
     * Lista as entradas de estoque com filtros e paginação.
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        $query = EntradaEstoque::with([
            'fornecedor',
            'usuario',
            'itens.material',
        ]);

        if (!empty($filtros['fornecedor_id'])) {
            $query->where(
                'fornecedor_id',
                $filtros['fornecedor_id']
            );
        }

        if (!empty($filtros['data_inicio'])) {
            $query->whereDate(
                'data_entrada',
                '>=',
                $filtros['data_inicio']
            );
        }

        if (!empty($filtros['data_fim'])) {
            $query->whereDate(
                'data_entrada',
                '<=',
                $filtros['data_fim']
            );
        }

        if (!empty($filtros['numero_nota_fiscal'])) {
            $query->where(
                'numero_nota_fiscal',
                'like',
                '%' . $filtros['numero_nota_fiscal'] . '%'
            );
        }

        if (!empty($filtros['busca'])) {
            $busca = $filtros['busca'];

            $query->whereHas('itens.material', function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%");
            });
        }

        return $query
            ->orderByDesc('data_entrada')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Consulta uma entrada específica.
     */
    public function buscarPorId(int $id): EntradaEstoque
    {
        return EntradaEstoque::with([
            'fornecedor',
            'usuario',
            'itens.material.categoria',
        ])->findOrFail($id);
    }

}
