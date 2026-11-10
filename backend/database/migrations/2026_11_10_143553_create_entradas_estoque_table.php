<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entradas_estoque', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fornecedor_id')
                ->nullable()
                ->constrained('fornecedores')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('numero_nota_fiscal', 50)->nullable();
            $table->string('arquivo_nota_fiscal')->nullable();

            $table->date('data_entrada');
            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas_estoque');
    }
};