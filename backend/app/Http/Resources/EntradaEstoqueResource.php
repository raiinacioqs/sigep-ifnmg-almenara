<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntradaEstoqueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'fornecedor_id' => $this->fornecedor_id,

            'fornecedor' => $this->whenLoaded('fornecedor', function () {
                return [
                    'id' => $this->fornecedor->id,
                    'razao_social' => $this->fornecedor->razao_social,
                    'nome_fantasia' => $this->fornecedor->nome_fantasia,
                    'cnpj' => $this->fornecedor->cnpj,
                ];
            }),

            'usuario_id' => $this->usuario_id,

            'usuario' => $this->whenLoaded('usuario', function () {
                return [
                    'id' => $this->usuario->id,
                    'nome' => $this->usuario->name,
                    'email' => $this->usuario->email,
                ];
            }),

            'numero_nota_fiscal' => $this->numero_nota_fiscal,
            'arquivo_nota_fiscal' => $this->arquivo_nota_fiscal,
            'data_entrada' => $this->data_entrada?->format('Y-m-d'),
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

                        'quantidade' => $item->quantidade,
                        'valor_unitario' => $item->valor_unitario,
                    ];
                });
            }),

            'criado_em' => $this->created_at,
            'atualizado_em' => $this->updated_at,
        ];
    }
}
