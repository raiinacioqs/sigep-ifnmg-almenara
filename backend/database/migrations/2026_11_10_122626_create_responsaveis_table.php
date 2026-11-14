
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsaveis', function (Blueprint $table) {
            $table->id();
            $table->string('siape', 20)->unique();
            $table->string('nome', 150);
            $table->string('email', 150);
            $table->string('cargo', 100);

            $table->foreignId('setor_id')
                ->constrained('setores')
                ->restrictOnDelete();

            $table->string('status', 20)->default('ativo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsaveis');
    }
};
