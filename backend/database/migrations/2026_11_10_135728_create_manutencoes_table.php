<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manutencoes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bem_patrimonial_id')
                ->constrained('bens_patrimoniais')
                ->restrictOnDelete();

            $table->string('tipo', 30);
            $table->text('descricao');

            $table->date('data_abertura');
            $table->date('data_conclusao')->nullable();

            $table->string('prestador', 150)->nullable();
            $table->decimal('custo', 12, 2)->nullable();

            $table->string('status', 30)->default('aberta');
            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manutencoes');
    }
};
