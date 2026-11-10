<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemEntradaEstoque extends Model
{
    protected $table = 'itens_entrada_estoque';

    protected $fillable = [
        'entrada_estoque_id',
        'material_id',
        'quantidade',
        'valor_unitario',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:3',
            'valor_unitario' => 'decimal:2',
        ];
    }

    public function entradaEstoque(): BelongsTo
    {
        return $this->belongsTo(EntradaEstoque::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}