<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntradaEstoqueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fornecedor_id' => [
                'nullable',
                'integer',
                Rule::exists('fornecedores', 'id')
                    ->where(fn ($query) => $query->where('status', 'ativo')),
            ],

            'numero_nota_fiscal' => [
                'nullable',
                'string',
                'max:50',
            ],

            'arquivo_nota_fiscal' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],

            'data_entrada' => [
                'required',
                'date',
            ],

            'observacao' => [
                'nullable',
                'string',
            ],

            'itens' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'itens.*.material_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('materiais', 'id')
                    ->where(fn ($query) => $query->where('status', 'ativo')),
            ],

            'itens.*.quantidade' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999.999',
            ],

            'itens.*.valor_unitario' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fornecedor_id.exists' =>
                'O fornecedor informado não existe ou está inativo.',

            'numero_nota_fiscal.max' =>
                'O número da nota fiscal deve ter no máximo 50 caracteres.',

            'arquivo_nota_fiscal.mimes' =>
                'O arquivo da nota fiscal deve estar no formato PDF.',

            'arquivo_nota_fiscal.max' =>
                'O arquivo da nota fiscal não pode ultrapassar 10 MB.',

            'data_entrada.required' =>
                'A data da entrada é obrigatória.',

            'itens.required' =>
                'Informe pelo menos um item para a entrada.',

            'itens.min' =>
                'A entrada deve possuir pelo menos um item.',

            'itens.*.material_id.required' =>
                'O material é obrigatório para cada item.',

            'itens.*.material_id.exists' =>
                'Um dos materiais informados não existe ou está inativo.',

            'itens.*.material_id.distinct' =>
                'Não repita o mesmo material na entrada. Informe sua quantidade em um único item.',

            'itens.*.quantidade.required' =>
                'A quantidade é obrigatória.',

            'itens.*.quantidade.gt' =>
                'A quantidade deve ser maior que zero.',

            'itens.*.valor_unitario.min' =>
                'O valor unitário não pode ser negativo.',
        ];
    }
}