<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarUsuarioAtivo
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $usuario = $request->user();

        if (!$usuario || $usuario->status !== 'ativo') {
            return response()->json([
                'message' => 'Usuário inativo ou sem autorização para acessar o sistema.',
            ], 403);
        }

        return $next($request);
    }
}
