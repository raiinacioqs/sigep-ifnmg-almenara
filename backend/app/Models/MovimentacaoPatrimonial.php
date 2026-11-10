<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoPatrimonial extends Model
{
    protected $table = 'movimentacoes_patrimoniais';

    protected $fillable = [
        'bem_patrimonial_id',
        'setor_origem_id',
        'setor_destino_id',
        'responsavel_origem_id',
        'responsavel_destino_id',
        'usuario_id',
        'tipo',
        'data_movimentacao',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_movimentacao' => 'datetime',
        ];
    }

    public function bemPatrimonial(): BelongsTo
    {
        return $this->belongsTo(BemPatrimonial::class);
    }

    public function setorOrigem(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_origem_id');
    }

    public function setorDestino(): BelongsTo
    {
        return $this->belongsTo(Setor::class, 'setor_destino_id');
    }

    public function responsavelOrigem(): BelongsTo
    {
        return $this->belongsTo(Responsavel::class, 'responsavel_origem_id');
    }

    public function responsavelDestino(): BelongsTo
    {
        return $this->belongsTo(Responsavel::class, 'responsavel_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
