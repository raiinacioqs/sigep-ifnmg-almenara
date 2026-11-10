<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntradaEstoque extends Model
{
    protected $table = 'entradas_estoque';

    protected $fillable = [
        'fornecedor_id',
        'usuario_id',
        'numero_nota_fiscal',
        'arquivo_nota_fiscal',
        'data_entrada',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_entrada' => 'date',
        ];
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemEntradaEstoque::class);
    }
}