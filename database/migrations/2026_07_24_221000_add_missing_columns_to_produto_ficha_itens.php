<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produção tinha produto_ficha_itens sem colunas da receita nova (ordem, unidade…).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('produto_ficha_itens')) {
            return;
        }

        Schema::table('produto_ficha_itens', function (Blueprint $table) {
            if (! Schema::hasColumn('produto_ficha_itens', 'insumo_id')) {
                $table->unsignedBigInteger('insumo_id')->nullable()->after('produto_id');
            }
            if (! Schema::hasColumn('produto_ficha_itens', 'unidade')) {
                $table->string('unidade', 8)->default('g')->after('quantidade');
            }
            if (! Schema::hasColumn('produto_ficha_itens', 'quantidade_base')) {
                $table->decimal('quantidade_base', 14, 3)->default(0)->after('unidade');
            }
            if (! Schema::hasColumn('produto_ficha_itens', 'observacao')) {
                $table->string('observacao', 200)->nullable();
            }
            if (! Schema::hasColumn('produto_ficha_itens', 'ordem')) {
                $table->unsignedSmallInteger('ordem')->default(0);
            }
        });

        // Se ainda existir o modelo antigo (produto como insumo), tenta não quebrar.
        if (Schema::hasColumn('produto_ficha_itens', 'quantidade_base')
            && Schema::hasColumn('produto_ficha_itens', 'quantidade')) {
            // Preenche base vazia com a quantidade cadastrada (já na unidade base).
            \Illuminate\Support\Facades\DB::table('produto_ficha_itens')
                ->whereNull('quantidade_base')
                ->orWhere('quantidade_base', 0)
                ->update([
                    'quantidade_base' => \Illuminate\Support\Facades\DB::raw('quantidade'),
                ]);
        }
    }

    public function down(): void
    {
        // Não remove — reparo.
    }
};
