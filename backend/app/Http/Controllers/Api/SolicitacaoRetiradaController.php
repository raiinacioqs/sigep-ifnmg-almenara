<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSolicitacaoRetiradaRequest;
use App\Http\Resources\SolicitacaoRetiradaResource;
use App\Services\SolicitacaoRetiradaService;
use Illuminate\Http\Request;

class SolicitacaoRetiradaController extends Controller
{
    protected SolicitacaoRetiradaService $solicitacaoRetiradaService;

    public function __construct(
        SolicitacaoRetiradaService $solicitacaoRetiradaService
    ) {
        $this->solicitacaoRetiradaService = $solicitacaoRetiradaService;
    }

    /**
     * Registra uma nova solicitação de retirada.
     */
    public function store(StoreSolicitacaoRetiradaRequest $request)
    {
        $solicitacao = $this->solicitacaoRetiradaService->criar(
            $request->validated(),
            $request->user()
        );

        return (new SolicitacaoRetiradaResource($solicitacao))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Lista as solicitações de retirada.
     */
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'status' => [
                'sometimes',
                'in:pendente,atendida,rejeitada',
            ],
        ]);

        $solicitacoes = $this->solicitacaoRetiradaService
            ->listar($filtros);

        return SolicitacaoRetiradaResource::collection(
            $solicitacoes
        );
    }

    /**
     * Consulta uma solicitação específica.
     */
    public function show(int $id)
    {
        $solicitacao = $this->solicitacaoRetiradaService
            ->buscarPorId($id);

        if (!$solicitacao) {
            abort(404, 'Solicitação de retirada não encontrada.');
        }

        return new SolicitacaoRetiradaResource($solicitacao);
    }

    /**
     * Realiza o atendimento de uma solicitação de retirada.
     */
    public function atender(int $id, Request $request)
    {
        $solicitacao = $this->solicitacaoRetiradaService->atender(
            $id,
            $request->user()
        );

        return new SolicitacaoRetiradaResource($solicitacao);
    }

    /**
     * Lista somente as solicitações do servidor autenticado.
     */
    public function minhasSolicitacoes(Request $request)
    {
        $filtros = $request->validate([
            'status' => [
                'sometimes',
                'in:pendente,atendida,rejeitada',
            ],
        ]);

        $solicitacoes = $this->solicitacaoRetiradaService
            ->listarDoUsuario($filtros, $request->user());

        return SolicitacaoRetiradaResource::collection(
            $solicitacoes
        );
    }
}