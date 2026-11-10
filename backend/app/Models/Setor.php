<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setor extends Model
{
    protected $table = 'setores';

    protected $fillable = [
        'nome',
        'sigla',
        'localizacao',
        'status',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function responsaveis(): HasMany
    {
        return $this->hasMany(Responsavel::class);
    }
}