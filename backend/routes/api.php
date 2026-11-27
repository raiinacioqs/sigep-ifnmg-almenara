<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\FornecedorController;
use App\Http\Controllers\Api\SetorController;
use App\Http\Controllers\Api\ResponsavelController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\EntradaEstoqueController;
use App\Http\Controllers\Api\SolicitacaoRetiradaController;
use App\Http\Controllers\Api\Admin\UsuarioController;
use App\Http\Controllers\Api\MovimentacaoPatrimonialController;
use App\Http\Controllers\Api\ManutencaoController;
use App\Http\Controllers\Api\BemPatrimonialController;

// Rota pública
Route::post('/login', [AuthController::class, 'login']);

// Rotas para usuários autenticados
Route::middleware(['auth:sanctum', 'usuario.ativo'])->group(function () {

    Route::get('/user', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Somente administrador
    Route::middleware('perfil:Administrador')
        ->prefix('admin')
        ->group(function () {

            // Gestão de usuários
            Route::get('/usuarios', [UsuarioController::class, 'index']);

            Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update']);

            Route::patch(
                '/usuarios/{usuario}/status',
                [UsuarioController::class, 'atualizarStatus']
            );

            Route::get('/teste', function () {
                return response()->json([
                    'message' => 'Acesso autorizado ao administrador.',
                ]);
            });

            // Consulta de usuários cadastrados
            Route::get('/usuarios', [UsuarioController::class, 'index']);
        });

    // Administrador e gestor do almoxarifado
    Route::middleware('perfil:Administrador,Gestor de Almoxarifado')
        ->prefix('gestao')
        ->group(function () {

            Route::get('/teste', function () {
                return response()->json([
                    'message' => 'Acesso autorizado à gestão do SIGEP.',
                ]);
            });

            // Consulta das solicitações de retirada
            Route::get(
                '/solicitacoes-retirada',
                [SolicitacaoRetiradaController::class, 'index']
            );

            // Consulta das movimentações patrimoniais
            Route::get(
                '/movimentacoes-patrimoniais',
                [MovimentacaoPatrimonialController::class, 'index']
            );
            
            // Consulta de movimentações de estoque
            Route::get(
                '/movimentacoes-estoque',
                [MovimentacaoPatrimonialController::class, 'estoque']
            );
            // Consulta de uma solicitação específica
            Route::get(
                '/solicitacoes-retirada/{id}',
                [SolicitacaoRetiradaController::class, 'show']
            );

            // Dados da manutenção pelo gestor de almoxarifado
            Route::get('/manutencoes', [ManutencaoController::class, 'index']);
            Route::post('/manutencoes', [ManutencaoController::class, 'store']);

            // Consulta de bens patrimoniais
            Route::get(
                '/bens-patrimoniais',
                [BemPatrimonialController::class, 'index']
            );

            // Cadastro de bem patrimonial
            Route::post(
                '/bens-patrimoniais',
                [BemPatrimonialController::class, 'store']
            );

            // Consulta individual de bem patrimonial
            Route::get('/bens-patrimoniais/{id}', [
                BemPatrimonialController::class,
                'show'
            ]);

            // Edição do bem patrimonial
            Route::put('/bens-patrimoniais/{id}', [
                BemPatrimonialController::class,
                'update'
            ]);

            // Atendimento restrito ao Gestor de Almoxarifado
            Route::middleware('perfil:Gestor de Almoxarifado')->group(function () {
                Route::post(
                    '/solicitacoes-retirada/{id}/atender',
                    [SolicitacaoRetiradaController::class, 'atender']
                );
            });
        });

    // Servidor
    Route::middleware('perfil:Servidor')->prefix('servidor')->group(function () {

        Route::get('/teste', function () {
            return response()->json([
                'message' => 'Acesso autorizado ao servidor.',
            ]);
        });

        // Consulta do histórico de solicitações do servidor autenticado
        Route::get(
            '/solicitacoes-retirada',
            [SolicitacaoRetiradaController::class, 'minhasSolicitacoes']
        );


        // Abertura de solicitação de retirada
        Route::post(
            '/solicitacoes-retirada',
            [SolicitacaoRetiradaController::class, 'store']
        );
    });

    // Categorias: consulta para usuários autenticados
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/categorias/{id}', [CategoriaController::class, 'show']);

    // Categorias: operações restritas à gestão
    Route::middleware('perfil:Gestor de Almoxarifado')
        ->group(function () {
            Route::post('/categorias', [CategoriaController::class, 'store']);
            Route::put('/categorias/{categoria}', [CategoriaController::class, 'update']);
            Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);
        });

    // Consulta de fornecedores para usuários autenticados
    Route::get('/fornecedores', [FornecedorController::class, 'index']);
    Route::get('/fornecedores/{id}', [FornecedorController::class, 'show']);

    // Operações de gestão de fornecedores
    Route::middleware('perfil:Administrador,Gestor de Almoxarifado')
        ->group(function () {
            Route::post('/fornecedores', [FornecedorController::class, 'store']);
            Route::put('/fornecedores/{id}', [FornecedorController::class, 'update']);
            Route::delete('/fornecedores/{id}', [FornecedorController::class, 'destroy']);
        });

    // Consulta de setores para usuários autenticados
    Route::get('/setores', [SetorController::class, 'index']);
    Route::get('/setores/{id}', [SetorController::class, 'show']);

    // Operações de gestão de setores
    Route::middleware('perfil:Administrador,Gestor de Almoxarifado')
        ->group(function () {
            Route::post('/setores', [SetorController::class, 'store']);
            Route::put('/setores/{id}', [SetorController::class, 'update']);
            Route::delete('/setores/{id}', [SetorController::class, 'destroy']);
        });
    
    // Consulta de responsáveis para usuários autenticados
    Route::get('/responsaveis', [ResponsavelController::class, 'index']);
    Route::get('/responsaveis/{id}', [ResponsavelController::class, 'show']);

    // Operações de gestão de responsáveis
    Route::middleware('perfil:Administrador,Gestor de Almoxarifado')
        ->group(function () {
            Route::post('/responsaveis', [ResponsavelController::class, 'store']);
            Route::put('/responsaveis/{id}', [ResponsavelController::class, 'update']);
            Route::delete('/responsaveis/{id}', [ResponsavelController::class, 'destroy']);
        });

    // Consulta de materiais para usuários autenticados
    Route::get('/materiais', [MaterialController::class, 'index']);
    Route::get('/materiais/{id}', [MaterialController::class, 'show']);

    // Operações de gestão de materiais
    Route::middleware('perfil:Gestor de Almoxarifado')
        ->group(function () {
            Route::post('/materiais', [MaterialController::class, 'store']);
            Route::put('/materiais/{id}', [MaterialController::class, 'update']);
            Route::delete('/materiais/{id}', [MaterialController::class, 'destroy']);
        });

    
    // Entradas de estoque: operações restritas à gestão
    Route::middleware('perfil:Administrador,Gestor de Almoxarifado')
        ->group(function () {

            // Cadastro de entrada
            Route::post(
                '/entradas-estoque',
                [EntradaEstoqueController::class, 'store']
            );

            // Consulta das entradas
            Route::get(
                '/entradas-estoque',
                [EntradaEstoqueController::class, 'index']
            );

            // Consulta de uma entrada específica
            Route::get(
                '/entradas-estoque/{id}',
                [EntradaEstoqueController::class, 'show']
            );

            // Download da nota fiscal
            Route::get(
                '/entradas-estoque/{id}/nota-fiscal',
                [EntradaEstoqueController::class, 'baixarNotaFiscal']
            );
        });

});