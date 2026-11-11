<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FornecedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'razao_social' => $this->razao_social,
            'nome_fantasia' => $this->nome_fantasia,
            'cnpj' => $this->cnpj,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'endereco' => $this->endereco,
            'status' => $this->status,
            'criado_em' => $this->created_at,
            'atualizado_em' => $this->updated_at,
        ];
    }
}
