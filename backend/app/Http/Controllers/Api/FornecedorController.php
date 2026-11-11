<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFornecedorRequest;
use App\Http\Requests\Api\UpdateFornecedorRequest;
use App\Http\Resources\FornecedorResource;
use App\Models\Fornecedor;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    /**
     * Lista os fornecedores cadastrados.
     */
    public function index(Request $request)
    {
        $query = Fornecedor::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('razao_social', 'like', "%{$busca}%")
                    ->orWhere('nome_fantasia', 'like', "%{$busca}%")
                    ->orWhere('cnpj', 'like', "%{$busca}%");
            });
        }

        $fornecedores = $query
            ->orderBy('razao_social')
            ->paginate(10);

        return FornecedorResource::collection($fornecedores);
    }

    /**
     * Cadastra um fornecedor.
     */
    public function store(StoreFornecedorRequest $request)
    {
        $dados = $request->validated();

        $dados['status'] = $dados['status'] ?? 'ativo';

        $fornecedor = Fornecedor::create($dados);

        return (new FornecedorResource($fornecedor))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe um fornecedor específico.
     */
    public function show(string $id)
    {
        $fornecedor = Fornecedor::findOrFail($id);

        return new FornecedorResource($fornecedor);
    }

    /**
     * Atualiza os dados de um fornecedor.
     */
    public function update(
        UpdateFornecedorRequest $request,
        string $id
    ) {
        $fornecedor = Fornecedor::findOrFail($id);

        $fornecedor->update($request->validated());

        return new FornecedorResource($fornecedor->fresh());
    }

    /**
     * Inativa um fornecedor sem excluir seu histórico.
     */
    public function destroy(string $id)
    {
        $fornecedor = Fornecedor::findOrFail($id);

        $fornecedor->update([
            'status' => 'inativo',
        ]);

        return response()->json([
            'message' => 'Fornecedor inativado com sucesso.',
        ]);
    }
}
