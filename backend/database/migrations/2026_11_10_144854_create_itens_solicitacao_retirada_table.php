<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_solicitacao_retirada', function (Blueprint $table) {
            $table->id();

            $table->foreignId('solicitacao_retirada_id')
                ->constrained('solicitacoes_retirada')
                ->restrictOnDelete();

            $table->foreignId('material_id')
                ->nullable()
                ->constrained('materiais')
                ->restrictOnDelete();

            $table->foreignId('bem_patrimonial_id')
                ->nullable()
                ->constrained('bens_patrimoniais')
                ->restrictOnDelete();

            $table->decimal('quantidade_solicitada', 12, 3);

            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_solicitacao_retirada');
    }
};