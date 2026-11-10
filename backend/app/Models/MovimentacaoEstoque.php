<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoEstoque extends Model
{
    protected $table = 'movimentacoes_estoque';

    protected $fillable = [
        'material_id',
        'usuario_id',
        'solicitacao_retirada_id',
        'tipo',
        'quantidade',
        'data_movimentacao',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:3',
            'data_movimentacao' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function solicitacaoRetirada(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoRetirada::class);
    }
}