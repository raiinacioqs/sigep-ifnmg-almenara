<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMaterialRequest;
use App\Http\Requests\Api\UpdateMaterialRequest;
use App\Http\Resources\MaterialResource;
use App\Services\MaterialService;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    protected MaterialService $materialService;

    public function __construct(MaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    /**
     * Lista os materiais cadastrados.
     */
    public function index(Request $request)
    {
        $materiais = $this->materialService->listar(
            $request->only(['status', 'categoria_id', 'busca'])
        );

        return MaterialResource::collection($materiais);
    }

    /**
     * Cadastra um novo material.
     */
    public function store(StoreMaterialRequest $request)
    {
        $material = $this->materialService->cadastrar(
            $request->validated()
        );

        return (new MaterialResource($material))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe um material específico.
     */
    public function show(string $id)
    {
        $material = $this->materialService->buscarPorId((int) $id);

        return new MaterialResource($material);
    }

    /**
     * Atualiza os dados cadastrais do material.
     */
    public function update(UpdateMaterialRequest $request, string $id)
    {
        $material = $this->materialService->atualizar(
            (int) $id,
            $request->validated()
        );

        return new MaterialResource($material);
    }

    /**
     * Inativa um material sem excluir seu histórico.
     */
    public function destroy(string $id)
    {
        $this->materialService->inativar((int) $id);

        return response()->json([
            'message' => 'Material inativado com sucesso.',
        ]);
    }
}
