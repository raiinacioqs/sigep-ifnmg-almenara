<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitacaoRetiradaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'usuario_id' => $this->usuario_id,

            'usuario' => $this->whenLoaded('usuario', function () {
                return [
                    'id' => $this->usuario->id,
                    'nome' => $this->usuario->name,
                    'email' => $this->usuario->email,
                ];
            }),

            'setor_id' => $this->setor_id,

            'setor' => $this->whenLoaded('setor', function () {
                return [
                    'id' => $this->setor->id,
                    'nome' => $this->setor->nome,
                    'sigla' => $this->setor->sigla,
                ];
            }),

            'data_solicitacao' => $this->data_solicitacao?->format('Y-m-d'),
            'data_necessaria' => $this->data_necessaria?->format('Y-m-d'),
            'data_limite' => $this->data_limite?->format('Y-m-d'),

            'justificativa' => $this->justificativa,
            'status' => $this->status,
            'observacao' => $this->observacao,

            'itens' => $this->whenLoaded('itens', function () {
                return $this->itens->map(function ($item) {
                    return [
                        'id' => $item->id,

                        'material_id' => $item->material_id,

                        'material' => $item->material ? [
                            'id' => $item->material->id,
                            'nome' => $item->material->nome,
                            'unidade_medida' => $item->material->unidade_medida,
                        ] : null,

                        'bem_patrimonial_id' => $item->bem_patrimonial_id,

                        'bem_patrimonial' => $item->bemPatrimonial ? [
                            'id' => $item->bemPatrimonial->id,
                            'numero_tombo' => $item->bemPatrimonial->numero_tombo,
                            'descricao' => $item->bemPatrimonial->descricao,
                        ] : null,

                        'quantidade_solicitada' => $item->quantidade_solicitada,
                        'observacao' => $item->observacao,
                    ];
                });
            }),

            'analisado_por' => $this->analisado_por,
            'analisado_em' => $this->analisado_em,
            'criado_em' => $this->created_at,
            'atualizado_em' => $this->updated_at,
        ];
    }
}