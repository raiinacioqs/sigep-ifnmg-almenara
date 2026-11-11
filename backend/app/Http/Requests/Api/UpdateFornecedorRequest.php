<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fornecedorId = $this->route('id');

        return [
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => [
                'required',
                'string',
                'max:18',
                Rule::unique('fornecedores', 'cnpj')->ignore($fornecedorId),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['ativo', 'inativo'])],
        ];
    }

    public function messages(): array
    {
        return [
            'razao_social.required' => 'A razão social é obrigatória.',
            'cnpj.required' => 'O CNPJ é obrigatório.',
            'cnpj.unique' => 'Este CNPJ já pertence a outro fornecedor.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'O status deve ser ativo ou inativo.',
        ];
    }
}
