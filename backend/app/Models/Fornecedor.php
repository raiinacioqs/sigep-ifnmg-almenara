<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fornecedor extends Model
{
    protected $table = 'fornecedores';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'email',
        'telefone',
        'endereco',
        'status',
    ];

    public function bensPatrimoniais(): HasMany
    {
        return $this->hasMany(BemPatrimonial::class);
    }

    public function entradasEstoque(): HasMany
    {
        return $this->hasMany(EntradaEstoque::class);
    }
}
