<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSolicitacaoRetiradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_necessaria' => [
                'required',
                'date',
            ],

            'data_limite' => [
                'nullable',
                'date',
                'after_or_equal:data_necessaria',
            ],

            'justificativa' => [
                'required',
                'string',
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
                'nullable',
                'required_without:itens.*.bem_patrimonial_id',
                'integer',
                Rule::exists('materiais', 'id')
                    ->where(fn ($query) =>
                        $query->where('status', 'ativo')
                    ),
            ],

            'itens.*.bem_patrimonial_id' => [
                'nullable',
                'required_without:itens.*.material_id',
                'integer',
                Rule::exists('bens_patrimoniais', 'id')
                    ->where(fn ($query) =>
                        $query->where('status', 'ativo')
                    ),
            ],

            'itens.*.quantidade_solicitada' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999999.999',

                function ($attribute, $value, $fail) {
                    $partes = explode('.', $attribute);
                    $indice = $partes[1] ?? null;

                    if ($indice === null) {
                        return;
                    }

                    $bemPatrimonialId = $this->input(
                        "itens.{$indice}.bem_patrimonial_id"
                    );

                    if (
                        $bemPatrimonialId !== null &&
                        $bemPatrimonialId !== '' &&
                        (float) $value !== 1.0
                    ) {
                        $fail(
                            'A quantidade solicitada para um bem patrimonial deve ser exatamente 1.'
                        );
                    }
                },
            ],

            'itens.*.observacao' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'data_necessaria.required' =>
                'A data necessária é obrigatória.',

            'data_necessaria.date' =>
                'Informe uma data necessária válida.',

            'data_limite.after_or_equal' =>
                'A data limite não pode ser anterior à data necessária.',

            'justificativa.required' =>
                'A justificativa da solicitação é obrigatória.',

            'itens.required' =>
                'Informe pelo menos um item para a solicitação.',

            'itens.min' =>
                'A solicitação deve possuir pelo menos um item.',

            'itens.max' =>
                'A solicitação não pode ultrapassar 100 itens.',

            'itens.*.material_id.required_without' =>
                'Informe um material ou bem patrimonial para cada item.',

            'itens.*.material_id.prohibited_with' =>
                'Informe somente um tipo de item por registro.',

            'itens.*.material_id.exists' =>
                'O material informado não existe ou está inativo.',

            'itens.*.bem_patrimonial_id.required_without' =>
                'Informe um material ou bem patrimonial para cada item.',

            'itens.*.bem_patrimonial_id.prohibited_with' =>
                'Informe somente um tipo de item por registro.',

            'itens.*.bem_patrimonial_id.exists' =>
                'O bem patrimonial informado não existe ou está inativo.',

            'itens.*.quantidade_solicitada.required' =>
                'A quantidade solicitada é obrigatória.',

            'itens.*.quantidade_solicitada.gt' =>
                'A quantidade solicitada deve ser maior que zero.',
        ];
    }
}