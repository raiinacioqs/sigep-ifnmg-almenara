<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends FormRequest
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
                'max:150',
            ],

            'descricao' => [
                'nullable',
                'string',
            ],

            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')
                    ->where(fn ($query) => $query
                        ->where('tipo', 'consumo')
                        ->where('status', 'ativo')),
            ],

            'unidade_medida' => [
                'required',
                'string',
                'max:30',
            ],

            'quantidade_minima' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.999',
            ],

            'status' => [
                'required',
                Rule::in(['ativo', 'inativo']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do material é obrigatório.',
            'nome.max' => 'O nome deve ter no máximo 150 caracteres.',
            'categoria_id.required' => 'A categoria é obrigatória.',
            'categoria_id.exists' => 'A categoria informada não existe, está inativa ou não é de consumo.',
            'unidade_medida.required' => 'A unidade de medida é obrigatória.',
            'unidade_medida.max' => 'A unidade de medida deve ter no máximo 30 caracteres.',
            'quantidade_minima.required' => 'A quantidade mínima é obrigatória.',
            'quantidade_minima.numeric' => 'A quantidade mínima deve ser numérica.',
            'quantidade_minima.min' => 'A quantidade mínima não pode ser negativa.',
            'status.required' => 'O status do material é obrigatório.',
            'status.in' => 'O status deve ser ativo ou inativo.',
        ];
    }
}
