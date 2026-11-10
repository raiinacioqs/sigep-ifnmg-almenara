<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_retirada', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('setor_id')
                ->constrained('setores')
                ->restrictOnDelete();

            $table->date('data_solicitacao');
            $table->date('data_necessaria');
            $table->date('data_limite')->nullable();

            $table->text('justificativa');

            $table->string('status', 30)->default('pendente');

            $table->foreignId('analisado_por')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('analisado_em')->nullable();
            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_retirada');
    }
};