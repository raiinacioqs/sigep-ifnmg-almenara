<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPerfil
{
    /**
     * Verifica se o usuário possui um dos perfis permitidos.
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$perfis
    ): Response {
        $usuario = $request->user();

        if (!$usuario || !$usuario->role) {
            return response()->json([
                'message' => 'Usuário sem perfil de acesso definido.',
            ], 403);
        }

        if (!in_array($usuario->role->nome, $perfis, true)) {
            return response()->json([
                'message' => 'Você não tem permissão para acessar este recurso.',
            ], 403);
        }

        return $next($request);
    }
}
