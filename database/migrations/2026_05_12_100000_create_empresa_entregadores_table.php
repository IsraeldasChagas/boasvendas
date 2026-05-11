<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_entregadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome', 255);
            $table->string('whatsapp', 32);
            $table->string('foto', 500)->nullable();
            $table->string('moto_modelo', 120)->nullable();
            $table->string('moto_cor', 64)->nullable();
            $table->string('moto_placa', 16)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ordem']);
            $table->index(['empresa_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_entregadores');
    }
};
