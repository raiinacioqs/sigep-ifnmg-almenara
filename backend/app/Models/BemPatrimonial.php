<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BemPatrimonial extends Model
{
    protected $table = 'bens_patrimoniais';

    protected $fillable = [
        'numero_tombo',
        'descricao',
        'categoria_id',
        'fornecedor_id',
        'setor_id',
        'responsavel_id',
        'localizacao',
        'nota_fiscal',
        'data_aquisicao',
        'valor_aquisicao',
        'estado_conservacao',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'data_aquisicao' => 'date',
            'valor_aquisicao' => 'decimal:2',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Responsavel::class);
    }

    public function movimentacoes(): HasMany
    {
        return $this->hasMany(MovimentacaoPatrimonial::class);
    }

    public function manutencoes(): HasMany
    {
        return $this->hasMany(Manutencao::class);
    }
}
