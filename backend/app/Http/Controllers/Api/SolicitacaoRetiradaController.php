<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSolicitacaoRetiradaRequest;
use App\Http\Resources\SolicitacaoRetiradaResource;
use App\Services\SolicitacaoRetiradaService;

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
}