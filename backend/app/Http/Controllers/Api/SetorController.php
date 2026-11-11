<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSetorRequest;
use App\Http\Requests\Api\UpdateSetorRequest;
use App\Http\Resources\SetorResource;
use App\Models\Setor;
use Illuminate\Http\Request;

class SetorController extends Controller
{
    /**
     * Lista os setores cadastrados.
     */
    public function index(Request $request)
    {
        $query = Setor::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('sigla', 'like', "%{$busca}%");
            });
        }

        $setores = $query
            ->orderBy('nome')
            ->paginate(10);

        return SetorResource::collection($setores);
    }

    /**
     * Cadastra um novo setor.
     */
    public function store(StoreSetorRequest $request)
    {
        $dados = $request->validated();

        $dados['status'] = $dados['status'] ?? 'ativo';

        $setor = Setor::create($dados);

        return (new SetorResource($setor))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe um setor específico.
     */
    public function show(string $id)
    {
        $setor = Setor::findOrFail($id);

        return new SetorResource($setor);
    }

    /**
     * Atualiza os dados de um setor.
     */
    public function update(UpdateSetorRequest $request, string $id)
    {
        $setor = Setor::findOrFail($id);

        $setor->update($request->validated());

        return new SetorResource($setor->fresh());
    }

    /**
     * Inativa um setor sem excluir seu histórico.
     */
    public function destroy(string $id)
    {
        $setor = Setor::findOrFail($id);

        $setor->update([
            'status' => 'inativo',
        ]);

        return response()->json([
            'message' => 'Setor inativado com sucesso.',
        ]);
    }
}
