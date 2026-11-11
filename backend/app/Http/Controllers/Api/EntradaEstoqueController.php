<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEntradaEstoqueRequest;
use App\Http\Resources\EntradaEstoqueResource;
use App\Services\EntradaEstoqueService;
use Illuminate\Http\Request;

class EntradaEstoqueController extends Controller
{
    protected EntradaEstoqueService $entradaEstoqueService;

    public function __construct(
        EntradaEstoqueService $entradaEstoqueService
    ) {
        $this->entradaEstoqueService = $entradaEstoqueService;
    }

    /**
     * Lista as entradas de estoque.
     */
    public function index(Request $request)
    {
        $entradas = $this->entradaEstoqueService->listar(
            $request->only([
                'fornecedor_id',
                'data_inicio',
                'data_fim',
                'numero_nota_fiscal',
                'busca',
            ])
        );

        return EntradaEstoqueResource::collection($entradas);
    }

    /**
     * Exibe uma entrada específica.
     */
    public function show(string $id)
    {
        $entrada = $this->entradaEstoqueService->buscarPorId(
            (int) $id
        );

        return new EntradaEstoqueResource($entrada);
    }

    /**
     * Registra uma nova entrada de estoque.
     */
    public function store(StoreEntradaEstoqueRequest $request)
    {
        $entrada = $this->entradaEstoqueService->cadastrar(
            $request->validated(),
            $request->user()->id
        );

        return (new EntradaEstoqueResource($entrada))
            ->response()
            ->setStatusCode(201);
    }
}
