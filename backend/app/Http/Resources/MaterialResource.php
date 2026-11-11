<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'categoria_id' => $this->categoria_id,

            'categoria' => $this->whenLoaded('categoria', function () {
                return [
                    'id' => $this->categoria->id,
                    'nome' => $this->categoria->nome,
                    'tipo' => $this->categoria->tipo,
                ];
            }),

            'unidade_medida' => $this->unidade_medida,
            'quantidade_minima' => $this->quantidade_minima,

            'estoque' => $this->whenLoaded('estoque', function () {
                return [
                    'id' => $this->estoque->id,
                    'quantidade_atual' => $this->estoque->quantidade_atual,
                ];
            }),

            'status' => $this->status,
            'criado_em' => $this->created_at,
            'atualizado_em' => $this->updated_at,
        ];
    }
}
