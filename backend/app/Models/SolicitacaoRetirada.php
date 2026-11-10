<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitacaoRetirada extends Model
{
    protected $table = 'solicitacoes_retirada';

    protected $fillable = [
        'usuario_id',
        'setor_id',
        'data_solicitacao',
        'data_necessaria',
        'data_limite',
        'justificativa',
        'status',
        'analisado_por',
        'analisado_em',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_solicitacao' => 'date',
            'data_necessaria' => 'date',
            'data_limite' => 'date',
            'analisado_em' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function analisador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemSolicitacaoRetirada::class);
    }
}