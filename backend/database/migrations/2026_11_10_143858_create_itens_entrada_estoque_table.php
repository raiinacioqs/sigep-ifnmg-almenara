<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_entrada_estoque', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entrada_estoque_id')
                ->constrained('entradas_estoque')
                ->restrictOnDelete();

            $table->foreignId('material_id')
                ->constrained('materiais')
                ->restrictOnDelete();

            $table->decimal('quantidade', 12, 3);
            $table->decimal('valor_unitario', 12, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_entrada_estoque');
    }
};