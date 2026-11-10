<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes_estoque', function (Blueprint $table) {
            $table->id();

            $table->foreignId('material_id')
                ->constrained('materiais')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('solicitacao_retirada_id')
                ->nullable()
                ->constrained('solicitacoes_retirada')
                ->restrictOnDelete();

            $table->string('tipo', 20);
            $table->decimal('quantidade', 12, 3);
            $table->dateTime('data_movimentacao');

            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_estoque');
    }
};