<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Rota pública
Route::post('/login', [AuthController::class, 'login']);

// Rotas para usuários autenticados
Route::middleware('auth:sanctum')->group(function () {

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
});