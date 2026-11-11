<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'sigla' => $this->sigla,
            'localizacao' => $this->localizacao,
            'status' => $this->status,
            'criado_em' => $this->created_at,
            'atualizado_em' => $this->updated_at,
        ];
    }
}
