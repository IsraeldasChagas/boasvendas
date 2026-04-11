<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_entrega_faixas_cep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->char('cep_inicio', 8);
            $table->char('cep_fim', 8);
            $table->decimal('valor_taxa', 12, 2);
            $table->string('nome_regiao', 120)->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'cep_inicio', 'cep_fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresa_entrega_faixas_cep');
    }
};
