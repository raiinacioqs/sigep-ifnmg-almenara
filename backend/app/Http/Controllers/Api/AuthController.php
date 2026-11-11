<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Realiza a autenticação do usuário.
     */
    public function login(Request $request)
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = User::where('email', $dados['email'])->first();

        if (
            !$usuario ||
            !Hash::check($dados['password'], $usuario->password)
        ) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas são inválidas.'],
            ]);
        }

        if ($usuario->status !== 'ativo') {
            return response()->json([
                'message' => 'Usuário inativo. Entre em contato com o administrador.',
            ], 403);
        }

        $token = $usuario->createToken('sigep-api')->plainTextToken;

        return response()->json([
            'message' => 'Autenticação realizada com sucesso.',
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'nome' => $usuario->name,
                'email' => $usuario->email,
                'siape' => $usuario->siape,
                'status' => $usuario->status,
                'perfil' => $usuario->role?->nome,
                'setor' => $usuario->setor?->nome,
            ],
        ], 200);
    }

    /**
     * Encerra a sessão e revoga o token atual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ], 200);
    }

    /**
     * Retorna os dados do usuário autenticado.
     */
    public function me(Request $request)
    {
        $usuario = $request->user()->load(['role', 'setor']);

        return response()->json([
            'usuario' => [
                'id' => $usuario->id,
                'nome' => $usuario->name,
                'email' => $usuario->email,
                'siape' => $usuario->siape,
                'status' => $usuario->status,
                'perfil' => $usuario->role?->nome,
                'setor' => $usuario->setor?->nome,
            ],
        ], 200);
    }
}
