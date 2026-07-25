<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha técnica (receita): insumos com estoque próprio em unidade base
 * (g, ml, un) + quantidade consumida por porção do prato + modo de preparo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nome', 140);
            $table->string('foto', 512)->nullable();
            // Unidade base do saldo: g (peso), ml (volume) ou un (contagem).
            $table->string('unidade_base', 8)->default('g');
            $table->decimal('saldo', 14, 3)->default(0);
            $table->decimal('estoque_minimo', 14, 3)->default(0);
            $table->decimal('custo_unitario', 12, 4)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'ativo']);
            $table->unique(['empresa_id', 'nome']);
        });

        Schema::create('produto_ficha_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained('produtos')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            // Quantidade informada pelo usuário na unidade escolhida (ex.: 0.2 kg).
            $table->decimal('quantidade', 12, 3);
            $table->string('unidade', 8);
            // Equivalente em unidade base do insumo (ex.: 200 g), usado na baixa.
            $table->decimal('quantidade_base', 14, 3);
            $table->string('observacao', 200)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['produto_id', 'insumo_id']);
            $table->index(['empresa_id', 'produto_id']);
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->text('modo_preparo')->nullable()->after('descricao');
            // Quantas porções/unidades a ficha rende.
            $table->unsignedInteger('ficha_rendimento')->default(1)->after('modo_preparo');
            $table->unsignedSmallInteger('ficha_tempo_preparo_min')->nullable()->after('ficha_rendimento');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn(['modo_preparo', 'ficha_rendimento', 'ficha_tempo_preparo_min']);
        });

        Schema::dropIfExists('produto_ficha_itens');
        Schema::dropIfExists('insumos');
    }
};
