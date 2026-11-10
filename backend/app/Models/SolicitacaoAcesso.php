<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoAcesso extends Model
{
    protected $table = 'solicitacoes_acesso';

    protected $fillable = [
        'nome_completo',
        'email_institucional',
        'siape',
        'setor_id',
        'role_id',
        'status',
        'analisado_por',
        'analisado_em',
        'user_id',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'analisado_em' => 'datetime',
        ];
    }

    public function setor(): BelongsTo
    {
        return $this->belongsTo(Setor::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function analisador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analisado_por');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
