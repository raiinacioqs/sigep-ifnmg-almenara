<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aplica as alterações na tabela de responsáveis.
     */
    public function up(): void
    {
        Schema::table('responsaveis', function (Blueprint $table) {
            $table->string('email', 150)->nullable(false)->change();

            $table->string('cargo', 150)->nullable(false)->change();
        });
    }

    /**
     * Reverte as alterações.
     */
    public function down(): void
    {
        Schema::table('responsaveis', function (Blueprint $table) {
            $table->string('email', 150)->nullable()->change();

            $table->string('cargo', 100)->nullable()->change();
        });
    }
};
