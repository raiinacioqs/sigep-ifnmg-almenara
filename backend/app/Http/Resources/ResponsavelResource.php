<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponsavelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'siape' => $this->siape,
            'nome' => $this->nome,
            'email' => $this->email,
            'cargo' => $this->cargo,
            'setor_id' => $this->setor_id,
            'setor' => $this->whenLoaded('setor', function () {
                return [
                    'id' => $this->setor->id,
                    'nome' => $this->setor->nome,
                    'sigla' => $this->setor->sigla,
                ];
            }),
            'status' => $this->status,
            'criado_em' => $this->created_at,
            'atualizado_em' => $this->updated_at,
        ];
    }
}
