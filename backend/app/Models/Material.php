<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Material extends Model
{
    protected $table = 'materiais';

    protected $fillable = [
        'nome',
        'descricao',
        'categoria_id',
        'unidade_medida',
        'quantidade_minima',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantidade_minima' => 'decimal:3',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function estoque(): HasOne
    {
        return $this->hasOne(Estoque::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class);
    }

    public function itensEntrada(): HasMany
    {
        return $this->hasMany(ItemEntradaEstoque::class);
    }
}