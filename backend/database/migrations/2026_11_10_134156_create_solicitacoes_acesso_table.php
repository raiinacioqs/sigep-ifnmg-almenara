<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_acesso', function (Blueprint $table) {
            $table->id();

            $table->string('nome_completo', 150);
            $table->string('email_institucional', 150)->unique();
            $table->string('siape', 20)->unique();

            $table->foreignId('setor_id')
                ->constrained('setores')
                ->restrictOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->string('status', 20)->default('pendente');

            $table->foreignId('analisado_por')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('analisado_em')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('observacao')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_acesso');
    }
};
