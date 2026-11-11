<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreResponsavelRequest;
use App\Http\Requests\Api\UpdateResponsavelRequest;
use App\Http\Resources\ResponsavelResource;
use App\Models\Responsavel;
use Illuminate\Http\Request;

class ResponsavelController extends Controller
{
    /**
     * Lista os responsáveis cadastrados.
     */
    public function index(Request $request)
    {
        $query = Responsavel::with('setor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('setor_id')) {
            $query->where('setor_id', $request->setor_id);
        }

        if ($request->filled('busca')) {
            $busca = $request->busca;

            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                    ->orWhere('siape', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        $responsaveis = $query
            ->orderBy('nome')
            ->paginate(10);

        return ResponsavelResource::collection($responsaveis);
    }

    /**
     * Cadastra um responsável.
     */
    public function store(StoreResponsavelRequest $request)
    {
        $dados = $request->validated();

        $dados['status'] = $dados['status'] ?? 'ativo';

        $responsavel = Responsavel::create($dados);

        return (new ResponsavelResource(
            $responsavel->load('setor')
        ))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe um responsável específico.
     */
    public function show(string $id)
    {
        $responsavel = Responsavel::with('setor')->findOrFail($id);

        return new ResponsavelResource($responsavel);
    }

    /**
     * Atualiza os dados de um responsável.
     */
    public function update(
        UpdateResponsavelRequest $request,
        string $id
    ) {
        $responsavel = Responsavel::findOrFail($id);

        $responsavel->update($request->validated());

        return new ResponsavelResource(
            $responsavel->fresh()->load('setor')
        );
    }

    /**
     * Inativa um responsável sem excluir seu histórico.
     */
    public function destroy(string $id)
    {
        $responsavel = Responsavel::findOrFail($id);

        $responsavel->update([
            'status' => 'inativo',
        ]);

        return response()->json([
            'message' => 'Responsável inativado com sucesso.',
        ]);
    }
}
