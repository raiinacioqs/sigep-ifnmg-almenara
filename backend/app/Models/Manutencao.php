<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Manutencao extends Model
{
    protected $table = 'manutencoes';

    protected $fillable = [
        'bem_patrimonial_id',
        'tipo',
        'descricao',
        'data_abertura',
        'data_conclusao',
        'prestador',
        'custo',
        'status',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_abertura' => 'date',
            'data_conclusao' => 'date',
            'custo' => 'decimal:2',
        ];
    }

    public function bemPatrimonial(): BelongsTo
    {
        return $this->belongsTo(BemPatrimonial::class);
    }
}
