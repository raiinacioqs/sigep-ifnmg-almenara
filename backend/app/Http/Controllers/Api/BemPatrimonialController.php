<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BemPatrimonial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BemPatrimonialController extends Controller
{
    public function index()
    {
        $bens = BemPatrimonial::query()
            ->with([
                'categoria',
                'setor',
                'responsavel',
            ])
            ->select(
                'id',
                'numero_tombo',
                'descricao',
                'categoria_id',
                'setor_id',
                'responsavel_id',
                'localizacao',
                'data_aquisicao',
                'valor_aquisicao',
                'estado_conservacao',
                'status'
            )
            ->orderBy('numero_tombo')
            ->paginate(10);

        return response()->json($bens);
    }


    public function show($id)
    {
        $bem = BemPatrimonial::with([
            'categoria',
            'setor',
            'responsavel',
            'fornecedor',
        ])->findOrFail($id);

        return response()->json([
            'data' => $bem,
        ]);
    }

    public function update(Request $request, $id)
    {
        $bem = BemPatrimonial::findOrFail($id);

        $dados = $request->validate([
            'numero_tombo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('bens_patrimoniais', 'numero_tombo')
                    ->ignore($bem->id),
            ],
            'descricao' => ['required', 'string', 'max:255'],
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')
                    ->where('tipo', 'patrimonio')
                    ->where('status', 'ativo'),
            ],
            'fornecedor_id' => [
                'nullable',
                'integer',
                'exists:fornecedores,id',
            ],
            'setor_id' => [
                'required',
                'integer',
                'exists:setores,id',
            ],
            'responsavel_id' => [
                'nullable',
                'integer',
                'exists:responsaveis,id',
            ],
            'localizacao' => ['nullable', 'string'],
            'nota_fiscal' => ['nullable', 'string', 'max:50'],
            'data_aquisicao' => ['nullable', 'date'],
            'valor_aquisicao' => ['nullable', 'numeric', 'min:0'],
            'estado_conservacao' => ['nullable', 'string', 'max:30'],
        ]);

        $bem->update($dados);

        return response()->json([
            'message' => 'Bem patrimonial atualizado com sucesso.',
            'data' => $bem->load([
                'categoria',
                'setor',
                'responsavel',
                'fornecedor',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'numero_tombo' => [
                'required',
                'string',
                'max:50',
                'unique:bens_patrimoniais,numero_tombo',
            ],

            'descricao' => [
                'required',
                'string',
                'max:255',
            ],

            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')
                    ->where('tipo', 'patrimonio')
                    ->where('status', 'ativo'),
            ],

            'fornecedor_id' => [
                'nullable',
                'integer',
                'exists:fornecedores,id',
            ],

            'setor_id' => [
                'required',
                'integer',
                'exists:setores,id',
            ],

            'responsavel_id' => [
                'nullable',
                'integer',
                'exists:responsaveis,id',
            ],

            'localizacao' => [
                'nullable',
                'string',
            ],

            'nota_fiscal' => [
                'nullable',
                'string',
                'max:50',
            ],

            'data_aquisicao' => [
                'nullable',
                'date',
            ],

            'valor_aquisicao' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'estado_conservacao' => [
                'nullable',
                'string',
                'max:30',
            ],
        ]);

        $bem = BemPatrimonial::create($dados);

        return response()->json([
            'message' => 'Bem patrimonial cadastrado com sucesso.',
            'data' => $bem,
        ], 201);
    }
}
