<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCategoriaRequest;
use App\Http\Requests\Api\UpdateCategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Lista as categorias cadastradas.
     */
    public function index(Request $request)
    {
        $query = Categoria::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('busca')) {
            $query->where('nome', 'like', '%' . $request->busca . '%');
        }

        $categorias = $query
            ->orderBy('nome')
            ->paginate(10);

        return CategoriaResource::collection($categorias);
    }

    /**
     * Cadastra uma nova categoria.
     */
    public function store(StoreCategoriaRequest $request)
    {
        $dados = $request->validated();

        $dados['status'] = $dados['status'] ?? 'ativo';

        $categoria = Categoria::create($dados);

        return (new CategoriaResource($categoria))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe uma categoria específica.
     */
    public function show(string $id)
    {
        $categoria = Categoria::findOrFail($id);

        return new CategoriaResource($categoria);
    }

    /**
     * Atualiza uma categoria.
     */
    public function update(UpdateCategoriaRequest $request, string $id)
    {
        $categoria = Categoria::findOrFail($id);

        $categoria->update($request->validated());

        return new CategoriaResource($categoria->fresh());
    }

    /**
     * Inativa uma categoria sem excluir seu histórico.
     */
    public function destroy(string $id)
    {
        $categoria = Categoria::findOrFail($id);

        $categoria->update([
            'status' => 'inativo',
        ]);

        return response()->json([
            'message' => 'Categoria inativada com sucesso.',
        ]);
    }
}
