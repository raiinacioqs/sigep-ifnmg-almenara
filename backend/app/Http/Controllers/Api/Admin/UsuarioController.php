<?php

namespace App\Http\Controllers\Api\Admin;
use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::query()
            ->with(['role', 'setor'])
            ->when($request->filled('busca'), function ($query) use ($request) {
                $busca = $request->busca;

                $query->where(function ($subquery) use ($busca) {
                    $subquery->where('name', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%")
                        ->orWhere('siape', 'like', "%{$busca}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('role_id'), function ($query) use ($request) {
                $query->where('role_id', $request->role_id);
            })
            ->orderBy('name')
            ->paginate(10);

        return response()->json($usuarios);
    }

    public function update(Request $request, User $usuario)
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],

            'siape' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'siape')
                    ->ignore($usuario->id),
            ],

            'role_id' => [
                'required',
                'exists:roles,id',
            ],

            'setor_id' => [
                'nullable',
                'exists:setores,id',
            ],
        ]);

        $usuario->update($dados);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'usuario' => $usuario->load(['role', 'setor']),
        ]);
    }

    public function atualizarStatus(Request $request, User $usuario)
    {
        $dados = $request->validate([
            'status' => ['required', Rule::in(['ativo', 'inativo'])],
        ]);

        $usuario->update([
            'status' => $dados['status'],
        ]);

        return response()->json([
            'message' => 'Status do usuário atualizado com sucesso.',
            'usuario' => $usuario->load(['role', 'setor']),
        ]);
    }
}