<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Responsavel extends Model
{
    protected $table = 'responsaveis';

    protected $fillable = [
        'siape',
        'nome',
        'email',
        'cargo',
        'setor_id',
        'status',
    ];

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }
}
