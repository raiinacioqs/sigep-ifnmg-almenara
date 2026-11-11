<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResponsavelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siape' => [
                'required',
                'string',
                'max:20',
                Rule::unique('responsaveis', 'siape'),
            ],
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'cargo' => ['required', 'string', 'max:255'],
            'setor_id' => [
                'required',
                Rule::exists('setores', 'id')
                    ->where(fn ($query) => $query->where('status', 'ativo')),
            ],
            'status' => ['sometimes', Rule::in(['ativo', 'inativo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'siape.required' => 'O SIAPE é obrigatório.',
            'siape.unique' => 'Este SIAPE já está cadastrado.',
            'nome.required' => 'O nome do responsável é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'cargo.required' => 'O cargo é obrigatório.',
            'setor_id.required' => 'O setor é obrigatório.',
            'setor_id.exists' => 'O setor informado não existe ou está inativo.',
            'status.in' => 'O status deve ser ativo ou inativo.',
        ];
    }
}

