<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Estoque;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MaterialService
{
    /**
     * Lista materiais com filtros e paginação.
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        $query = Material::with(['categoria', 'estoque']);

        if (!empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        if (!empty($filtros['categoria_id'])) {
            $query->where('categoria_id', $filtros['categoria_id']);
        }

        if (!empty($filtros['busca'])) {
            $busca = $filtros['busca'];

            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('descricao', 'like', "%{$busca}%");
            });
        }

        return $query
            ->orderBy('nome')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * Cadastra um material e cria seu estoque inicial.
     */
    public function cadastrar(array $dados): Material
    {
        return DB::transaction(function () use ($dados) {

            $dados['status'] = $dados['status'] ?? 'ativo';

            $material = Material::create($dados);

            Estoque::create([
                'material_id' => $material->id,
                'quantidade_atual' => 0,
            ]);

            return $material->load(['categoria', 'estoque']);
        });
    }

    /**
     * Consulta um material específico.
     */
    public function buscarPorId(int $id): Material
    {
        return Material::with(['categoria', 'estoque'])
            ->findOrFail($id);
    }

    /**
     * Atualiza os dados cadastrais do material.
     *
     * Não altera a quantidade atual do estoque.
     */
    public function atualizar(int $id, array $dados): Material
    {
        return DB::transaction(function () use ($id, $dados) {

            $material = Material::findOrFail($id);

            $material->update($dados);

            return $material->fresh(['categoria', 'estoque']);
        });
    }

    /**
     * Inativa um material sem excluir seu histórico.
     */
    public function inativar(int $id): void
    {
        $material = Material::findOrFail($id);

        $material->update([
            'status' => 'inativo',
        ]);
    }
}
