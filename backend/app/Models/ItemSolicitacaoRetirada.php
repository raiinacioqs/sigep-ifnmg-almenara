<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSolicitacaoRetirada extends Model
{
    protected $table = 'itens_solicitacao_retirada';

    protected $fillable = [
        'solicitacao_retirada_id',
        'material_id',
        'bem_patrimonial_id',
        'quantidade_solicitada',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_solicitada' => 'decimal:3',
        ];
    }

    public function solicitacaoRetirada(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoRetirada::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function bemPatrimonial(): BelongsTo
    {
        return $this->belongsTo(BemPatrimonial::class);
    }
}