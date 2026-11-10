<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes_patrimoniais', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bem_patrimonial_id')
                ->constrained('bens_patrimoniais')
                ->restrictOnDelete();

            $table->foreignId('setor_origem_id')
                ->nullable()
                ->constrained('setores')
                ->restrictOnDelete();

            $table->foreignId('setor_destino_id')
                ->constrained('setores')
                ->restrictOnDelete();

            $table->foreignId('responsavel_origem_id')
                ->nullable()
                ->constrained('responsaveis')
                ->restrictOnDelete();

            $table->foreignId('responsavel_destino_id')
                ->nullable()
                ->constrained('responsaveis')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('tipo', 30);
            $table->dateTime('data_movimentacao');
            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_patrimoniais');
    }
};
