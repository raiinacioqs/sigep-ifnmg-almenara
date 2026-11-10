<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->enum('tipo', ['patrimonio', 'consumo']);
            $table->string('status', 20)->default('ativo');
            $table->timestamps();

            $table->unique(['nome', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};