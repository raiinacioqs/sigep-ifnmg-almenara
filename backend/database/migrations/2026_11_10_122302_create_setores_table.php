
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setores', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 120);
            $table->string('sigla', 20)->unique();
            $table->string('localizacao')->nullable();
            $table->string('status', 20)->default('ativo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setores');
    }

    private function criarSetor(array $sobrescrever = []): Setor
    {
        return Setor::create(array_merge([
            'nome' => 'Setor de Teste',
            'sigla' => 'ST',
            'localizacao' => 'Campus Almenara',
            'status' => 'ativo',
        ], $sobrescrever));
    }
};
