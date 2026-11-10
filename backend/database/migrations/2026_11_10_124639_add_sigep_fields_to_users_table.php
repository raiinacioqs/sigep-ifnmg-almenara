<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->nullable()
                ->constrained('roles')
                ->restrictOnDelete();

            $table->foreignId('setor_id')
                ->nullable()
                ->constrained('setores')
                ->restrictOnDelete();

            $table->string('siape', 20)
                ->nullable()
                ->unique();

            $table->string('status', 20)->default('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['setor_id']);
            $table->dropUnique(['users_siape_unique']);

            $table->dropColumn([
                'role_id',
                'setor_id',
                'siape',
                'status',
            ]);
        });
    }
};
