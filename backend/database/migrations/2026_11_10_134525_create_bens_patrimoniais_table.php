<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bens_patrimoniais', function (Blueprint $table) {
            $table->id();

            $table->string('numero_tombo', 50)->unique();
            $table->string('descricao', 255);

            $table->foreignId('categoria_id')
                ->constrained('categorias')
                ->restrictOnDelete();

            $table->foreignId('fornecedor_id')
                ->nullable()
                ->constrained('fornecedores')
                ->restrictOnDelete();

            $table->foreignId('setor_id')
                ->constrained('setores')
                ->restrictOnDelete();

            $table->foreignId('responsavel_id')
                ->nullable()
                ->constrained('responsaveis')
                ->restrictOnDelete();

            $table->string('localizacao')->nullable();
            $table->string('nota_fiscal', 50)->nullable();
            $table->date('data_aquisicao')->nullable();
            $table->decimal('valor_aquisicao', 12, 2)->nullable();
            $table->string('estado_conservacao', 30)->nullable();
            $table->string('status', 30)->default('ativo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bens_patrimoniais');
    }
};
