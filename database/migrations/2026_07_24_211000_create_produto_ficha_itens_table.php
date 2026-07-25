<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha técnica simples: quanto de cada insumo (outro produto) é consumido
 * ao vender 1 unidade do produto final. Baixa em cascata na venda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_ficha_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('insumo_produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->unsignedInteger('quantidade')->default(1);
            $table->timestamps();

            $table->unique(['produto_id', 'insumo_produto_id']);
            $table->index(['empresa_id', 'produto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_ficha_itens');
    }
};
