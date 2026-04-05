<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ve_pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('regiao')->nullable();
            $table->string('status', 16)->default('ativo');
            $table->dateTime('proximo_acerto_em')->nullable();
            $table->dateTime('ultimo_acerto_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ve_pontos');
    }
};
