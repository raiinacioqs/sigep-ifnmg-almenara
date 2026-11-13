<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categorias', 'nome')
                    ->where(fn ($query) => $query->where('tipo', $this->input('tipo'))),
            ],
            'descricao' => ['nullable', 'string'],
            'tipo' => ['required', Rule::in(['patrimonio', 'consumo'])],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da categoria é obrigatório.',
            'nome.unique' => 'Já existe uma categoria com esse nome e tipo.',
            'tipo.required' => 'O tipo da categoria é obrigatório.',
            'tipo.in' => 'O tipo deve ser patrimonio ou consumo.',
            'status.in' => 'O status deve ser ativo ou inativo.',
        ];
    }
}
