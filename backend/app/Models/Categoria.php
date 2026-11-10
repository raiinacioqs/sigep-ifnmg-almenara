<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'nome',
        'descricao',
        'tipo',
        'status',
    ];

    public function bensPatrimoniais(): HasMany
    {
        return $this->hasMany(BemPatrimonial::class);
    }

    public function materiais(): HasMany
    {
        return $this->hasMany(Material::class);
    }
}
