<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacoes_patrimoniais', function (Blueprint $table) {
            $table->foreignId('solicitacao_retirada_id')
                ->nullable()
                ->after('bem_patrimonial_id')
                ->constrained('solicitacoes_retirada')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimentacoes_patrimoniais', function (Blueprint $table) {
            $table->dropForeign(['solicitacao_retirada_id']);
            $table->dropColumn('solicitacao_retirada_id');
        });
    }
};