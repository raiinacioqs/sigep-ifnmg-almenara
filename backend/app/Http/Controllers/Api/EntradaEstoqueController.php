<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEntradaEstoqueRequest;
use App\Http\Resources\EntradaEstoqueResource;
use App\Services\EntradaEstoqueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     /**
     * Realiza o download da nota fiscal de uma entrada.
     */
    public function baixarNotaFiscal(string $id): StreamedResponse
    {
        $entrada = $this->entradaEstoqueService->buscarPorId(
            (int) $id
        );

        $caminho = $entrada->arquivo_nota_fiscal;

        if (
            !$caminho ||
            !Storage::disk('local')->exists($caminho)
        ) {
            abort(404, 'Nota fiscal não encontrada.');
        }

        return Storage::disk('local')->download(
            $caminho,
            'nota-fiscal-entrada-' . $entrada->id . '.pdf',
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }
}
