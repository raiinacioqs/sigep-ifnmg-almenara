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

// Rota pública
Route::post('/login', [AuthController::class, 'login']);

// Rotas para usuários autenticados
Route::middleware(['auth:sanctum', 'usuario.ativo'])->group(function () {

    Route::get('/user', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Somente administrador
    Route::middleware('perfil:Administrador')->prefix('admin')->group(function () {
        Route::get('/teste', function () {
            return response()->json([
                'message' => 'Acesso autorizado ao administrador.',
            ]);
        });
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
        });

    // Servidor
    Route::middleware('perfil:Servidor')->prefix('servidor')->group(function () {
        Route::get('/teste', function () {
            return response()->json([
                'message' => 'Acesso autorizado ao servidor.',
            ]);
        });
    });

    // Categorias: consulta para usuários autenticados
    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/categorias/{id}', [CategoriaController::class, 'show']);

    // Categorias: operações restritas à gestão
    Route::middleware('perfil:Administrador,Gestor de Almoxarifado')
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
    Route::middleware('perfil:Administrador,Gestor de Almoxarifado')
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