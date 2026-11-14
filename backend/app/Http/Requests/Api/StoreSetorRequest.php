<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSetorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'sigla' => [
                'required',
                'string',
                'max:20',
                Rule::unique('setores', 'sigla'),
            ],
            'localizacao' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do setor é obrigatório.',
            'sigla.required' => 'A sigla do setor é obrigatória.',
            'sigla.unique' => 'Esta sigla já está cadastrada.',
            'status.in' => 'O status deve ser ativo ou inativo.',
        ];
    }
}
