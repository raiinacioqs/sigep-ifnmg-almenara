<?php

namespace App\Services;

use App\Models\BemPatrimonial;
use App\Models\Categoria;
use App\Models\Material;
use App\Models\SolicitacaoRetirada;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Estoque;
use App\Models\MovimentacaoEstoque;
use App\Models\MovimentacaoPatrimonial;
use App\Models\Setor;

class SolicitacaoRetiradaService
{
    /**
     * Registra uma solicitação de retirada.
     *
     * A abertura não altera o estoque nem movimenta
     * bens patrimoniais.
     */
    public function criar(
        array $dados,
        User $usuario
    ): SolicitacaoRetirada {
        return DB::transaction(function () use ($dados, $usuario) {

            if (!$usuario->setor_id) {
                throw ValidationException::withMessages([
                    'setor_id' =>
                        'O usuário não possui setor vinculado.',
                ]);
            }

            $setor = $usuario->setor;

            if (!$setor || $setor->status !== 'ativo') {
                throw ValidationException::withMessages([
                    'setor_id' =>
                        'O setor do usuário não existe ou está inativo.',
                ]);
            }

            $solicitacao = SolicitacaoRetirada::create([
                'usuario_id' => $usuario->id,
                'setor_id' => $setor->id,
                'data_solicitacao' => now()->toDateString(),
                'data_necessaria' => $dados['data_necessaria'],
                'data_limite' => $dados['data_limite'] ?? null,
                'justificativa' => $dados['justificativa'],
                'status' => 'pendente',
                'observacao' => $dados['observacao'] ?? null,
            ]);

            foreach ($dados['itens'] as $indice => $item) {

                $temMaterial = !empty($item['material_id']);
                $temBem = !empty($item['bem_patrimonial_id']);

                if ($temMaterial === $temBem) {
                    throw ValidationException::withMessages([
                        "itens.{$indice}" =>
                            'Informe exatamente um material ou bem patrimonial.',
                    ]);
                }

                if ($temMaterial) {
                    $material = Material::query()
                        ->where('status', 'ativo')
                        ->find($item['material_id']);

                    if (!$material) {
                        throw ValidationException::withMessages([
                            "itens.{$indice}.material_id" =>
                                'Material não encontrado ou inativo.',
                        ]);
                    }

                    $categoria = Categoria::find($material->categoria_id);

                    if (
                        !$categoria ||
                        $categoria->status !== 'ativo' ||
                        $categoria->tipo !== 'consumo'
                    ) {
                        throw ValidationException::withMessages([
                            "itens.{$indice}.material_id" =>
                                'O material deve pertencer a uma categoria de consumo ativa.',
                        ]);
                    }
                }

                if ($temBem) {
                    $bem = BemPatrimonial::query()
                        ->where('status', 'ativo')
                        ->find($item['bem_patrimonial_id']);

                    if (!$bem) {
                        throw ValidationException::withMessages([
                            "itens.{$indice}.bem_patrimonial_id" =>
                                'Bem patrimonial não encontrado ou inativo.',
                        ]);
                    }

                    if (
                        !isset($item['quantidade_solicitada']) ||
                        !is_numeric($item['quantidade_solicitada']) ||
                        (float) $item['quantidade_solicitada'] !== 1.0
                    ) {
                        throw ValidationException::withMessages([
                            "itens.{$indice}.quantidade_solicitada" =>
                                'A quantidade solicitada para um bem patrimonial deve ser exatamente 1.',
                        ]);
                    }

                    $categoria = Categoria::find($bem->categoria_id);

                    if (
                        !$categoria ||
                        $categoria->status !== 'ativo' ||
                        $categoria->tipo !== 'patrimonio'
                    ) {
                        throw ValidationException::withMessages([
                            "itens.{$indice}.bem_patrimonial_id" =>
                                'O bem deve pertencer a uma categoria patrimonial ativa.',
                        ]);
                    }
                }

                $solicitacao->itens()->create([
                    'material_id' => $temMaterial
                        ? $item['material_id']
                        : null,

                    'bem_patrimonial_id' => $temBem
                        ? $item['bem_patrimonial_id']
                        : null,

                    'quantidade_solicitada' =>
                        $item['quantidade_solicitada'],

                    'observacao' => $item['observacao'] ?? null,
                ]);
            }

            return $solicitacao->load([
                'usuario',
                'setor',
                'itens.material.categoria',
                'itens.bemPatrimonial.categoria',
            ]);

        }, 3);
    }

    /**
     * Lista as solicitações de retirada.
     */
    public function listar(array $filtros = [])
    {
        $consulta = SolicitacaoRetirada::query()
            ->with([
                'usuario',
                'setor',
                'itens.material.categoria',
                'itens.bemPatrimonial.categoria',
            ])
            ->orderByDesc('data_solicitacao')
            ->orderByDesc('id');

        if (!empty($filtros['status'])) {
            $consulta->where('status', $filtros['status']);
        }

        return $consulta->paginate(10);
    }

        /**
     * Busca uma solicitação específica pelo ID.
     */
    public function buscarPorId(int $id): ?SolicitacaoRetirada
    {
        return SolicitacaoRetirada::query()
            ->with([
                'usuario',
                'setor',
                'itens.material.categoria',
                'itens.bemPatrimonial.categoria',
            ])
            ->find($id);
    }

    /**
     * Atende integralmente uma solicitação de retirada.
     *
     * Registra saídas de materiais de consumo e transferências
     * patrimoniais em uma única transação.
     */
    public function atender(
        int $id,
        User $gestor
    ): SolicitacaoRetirada {
        return DB::transaction(function () use ($id, $gestor) {

            // Bloqueia a solicitação para impedir atendimento duplicado.
            $solicitacao = SolicitacaoRetirada::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($solicitacao->status !== 'pendente') {
                throw ValidationException::withMessages([
                    'status' => 'Somente solicitações pendentes podem ser atendidas.',
                ]);
            }

            // Confere se o setor solicitante continua ativo.
            $setorDestino = Setor::query()
                ->lockForUpdate()
                ->find($solicitacao->setor_id);

            if (!$setorDestino || $setorDestino->status !== 'ativo') {
                throw ValidationException::withMessages([
                    'setor_id' => 'O setor de destino não existe ou está inativo.',
                ]);
            }

            $itens = $solicitacao->itens()
                ->orderBy('id')
                ->get();

            if ($itens->isEmpty()) {
                throw ValidationException::withMessages([
                    'itens' => 'A solicitação não possui itens.',
                ]);
            }

            $quantidadesMateriais = [];
            $idsBens = [];

            // Valida a estrutura dos itens e agrupa materiais repetidos.
            foreach ($itens as $item) {
                $temMaterial = !is_null($item->material_id);
                $temBem = !is_null($item->bem_patrimonial_id);

                if ($temMaterial === $temBem) {
                    throw ValidationException::withMessages([
                        'itens' => 'Cada item deve possuir exatamente um material ou bem patrimonial.',
                    ]);
                }

                if ($temMaterial) {
                    $quantidadeMil =
                        (int) round((float) $item->quantidade_solicitada * 1000);

                    if ($quantidadeMil <= 0) {
                        throw ValidationException::withMessages([
                            'itens' => 'Existe uma quantidade inválida na solicitação.',
                        ]);
                    }

                    $materialId = $item->material_id;

                    $quantidadesMateriais[$materialId] =
                        ($quantidadesMateriais[$materialId] ?? 0)
                        + $quantidadeMil;
                }

                if ($temBem) {
                    if ((float) $item->quantidade_solicitada !== 1.0) {
                        throw ValidationException::withMessages([
                            'itens' => 'A quantidade de cada bem patrimonial deve ser 1.',
                        ]);
                    }

                    if (in_array($item->bem_patrimonial_id, $idsBens)) {
                        throw ValidationException::withMessages([
                            'itens' => 'A solicitação contém o mesmo bem patrimonial mais de uma vez.',
                        ]);
                    }

                    $idsBens[] = $item->bem_patrimonial_id;
                }
            }

            // Valida e bloqueia materiais e estoques.
            ksort($quantidadesMateriais);

            $materiais = Material::query()
                ->whereIn('id', array_keys($quantidadesMateriais))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $estoques = Estoque::query()
                ->whereIn('material_id', array_keys($quantidadesMateriais))
                ->orderBy('material_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('material_id');

            foreach ($quantidadesMateriais as $materialId => $quantidadeMil) {
                $material = $materiais->get($materialId);
                $estoque = $estoques->get($materialId);

                if (!$material || $material->status !== 'ativo') {
                    throw ValidationException::withMessages([
                        'itens' => "O material {$materialId} não existe ou está inativo.",
                    ]);
                }

                $categoria = Categoria::query()
                    ->lockForUpdate()
                    ->find($material->categoria_id);

                if (
                    !$categoria ||
                    $categoria->status !== 'ativo' ||
                    $categoria->tipo !== 'consumo'
                ) {
                    throw ValidationException::withMessages([
                        'itens' => "O material {$materialId} não pertence a uma categoria de consumo ativa.",
                    ]);
                }

                if (!$estoque) {
                    throw ValidationException::withMessages([
                        'estoque' => "Não existe registro de estoque para o material {$materialId}.",
                    ]);
                }

                $disponivelMil =
                    (int) round((float) $estoque->quantidade_atual * 1000);

                if ($disponivelMil < $quantidadeMil) {
                    throw ValidationException::withMessages([
                        'estoque' => "Estoque insuficiente para o material {$material->nome}.",
                    ]);
                }
            }

            // Valida e bloqueia os bens patrimoniais.
            sort($idsBens);

            $bens = BemPatrimonial::query()
                ->whereIn('id', $idsBens)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($idsBens as $bemId) {
                $bem = $bens->get($bemId);

                if (!$bem || $bem->status !== 'ativo') {
                    throw ValidationException::withMessages([
                        'itens' => "O bem patrimonial {$bemId} não existe ou está inativo.",
                    ]);
                }

                $categoria = Categoria::query()
                    ->lockForUpdate()
                    ->find($bem->categoria_id);

                if (
                    !$categoria ||
                    $categoria->status !== 'ativo' ||
                    $categoria->tipo !== 'patrimonio'
                ) {
                    throw ValidationException::withMessages([
                        'itens' => "O bem patrimonial {$bemId} não pertence a uma categoria patrimonial ativa.",
                    ]);
                }
            }

            $dataMovimentacao = now();

            // Todas as validações passaram. Agora efetuamos as alterações.
            foreach ($quantidadesMateriais as $materialId => $quantidadeMil) {
                $quantidade = number_format(
                    $quantidadeMil / 1000,
                    3,
                    '.',
                    ''
                );

                $estoque = $estoques->get($materialId);

                $estoque->decrement(
                    'quantidade_atual',
                    $quantidade
                );

                MovimentacaoEstoque::create([
                    'material_id' => $materialId,
                    'usuario_id' => $gestor->id,
                    'solicitacao_retirada_id' => $solicitacao->id,
                    'tipo' => 'saida',
                    'quantidade' => $quantidade,
                    'data_movimentacao' => $dataMovimentacao,
                    'observacao' => "Saída referente à solicitação #{$solicitacao->id}.",
                ]);
            }

            foreach ($idsBens as $bemId) {
                $bem = $bens->get($bemId);

                $setorOrigemId = $bem->setor_id;
                $responsavelOrigemId = $bem->responsavel_id;

                $bem->setor_id = $setorDestino->id;
                $bem->save();

                MovimentacaoPatrimonial::create([
                    'bem_patrimonial_id' => $bem->id,
                    'solicitacao_retirada_id' => $solicitacao->id,
                    'setor_origem_id' => $setorOrigemId,
                    'setor_destino_id' => $setorDestino->id,
                    'responsavel_origem_id' => $responsavelOrigemId,
                    'responsavel_destino_id' => null,
                    'usuario_id' => $gestor->id,
                    'tipo' => 'transferencia',
                    'data_movimentacao' => $dataMovimentacao,
                    'observacao' => "Transferência referente à solicitação #{$solicitacao->id}.",
                ]);
            }

            // Finaliza a solicitação.
            $solicitacao->update([
                'status' => 'atendida',
                'analisado_por' => $gestor->id,
                'analisado_em' => $dataMovimentacao,
            ]);

            return $solicitacao->load([
                'usuario',
                'setor',
                'analisador',
                'itens.material.categoria',
                'itens.bemPatrimonial.categoria',
            ]);

        }, 3);
    }

    


}